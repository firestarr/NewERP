<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use App\Models\ItemStock;
use App\Models\Item;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = StockAdjustment::with(['adjustmentLines.item', 'adjustmentLines.warehouse']);

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('adjustment_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('adjustment_date', '<=', $request->date_to);
        }

        // Search by reference
        if ($request->has('search') && $request->search != '') {
            $query->where(function($q) use ($request) {
                $q->where('adjustment_reason', 'like', '%' . $request->search . '%')
                  ->orWhere('reference_document', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting
        $sortField = $request->get('sort_field', 'adjustment_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $adjustments = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $adjustments
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'adjustment_date' => 'required|date',
            'adjustment_reason' => 'required|string|max:255',
            'reference_document' => 'nullable|string|max:100',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,item_id',
            'lines.*.warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'lines.*.book_quantity' => 'required|numeric|min:0',
            'lines.*.adjusted_quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            // Create the stock adjustment header
            $adjustment = StockAdjustment::create([
                'adjustment_date' => $request->adjustment_date,
                'adjustment_reason' => $request->adjustment_reason,
                'reference_document' => $request->reference_document,
                'status' => StockAdjustment::STATUS_DRAFT
            ]);
            
            // Create the adjustment lines
            foreach ($request->lines as $line) {
                // Calculate variance
                $variance = $line['adjusted_quantity'] - $line['book_quantity'];
                
                // Create adjustment line
                StockAdjustmentLine::create([
                    'adjustment_id' => $adjustment->adjustment_id,
                    'item_id' => $line['item_id'],
                    'warehouse_id' => $line['warehouse_id'],
                    'book_quantity' => $line['book_quantity'],
                    'adjusted_quantity' => $line['adjusted_quantity'],
                    'variance' => $variance
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment created successfully',
                'data' => $adjustment->load('adjustmentLines.item', 'adjustmentLines.warehouse')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock adjustment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $adjustment = StockAdjustment::with(['adjustmentLines.item', 'adjustmentLines.warehouse'])
            ->find($id);

        if (!$adjustment) {
            return response()->json([
                'success' => false,
                'message' => 'Stock adjustment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $adjustment
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $adjustment = StockAdjustment::find($id);

        if (!$adjustment) {
            return response()->json([
                'success' => false,
                'message' => 'Stock adjustment not found'
            ], 404);
        }

        if ($adjustment->status != StockAdjustment::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft adjustments can be modified'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'adjustment_date' => 'required|date',
            'adjustment_reason' => 'required|string|max:255',
            'reference_document' => 'nullable|string|max:100',
            'lines' => 'required|array|min:1',
            'lines.*.line_id' => 'nullable|exists:stock_adjustment_lines,line_id',
            'lines.*.item_id' => 'required|exists:items,item_id',
            'lines.*.warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'lines.*.book_quantity' => 'required|numeric|min:0',
            'lines.*.adjusted_quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            // Update adjustment header
            $adjustment->update([
                'adjustment_date' => $request->adjustment_date,
                'adjustment_reason' => $request->adjustment_reason,
                'reference_document' => $request->reference_document
            ]);
            
            // Get current line IDs
            $currentLineIds = $adjustment->adjustmentLines()->pluck('line_id')->toArray();
            $updatedLineIds = [];
            
            // Process adjustment lines
            foreach ($request->lines as $lineData) {
                $variance = $lineData['adjusted_quantity'] - $lineData['book_quantity'];
                
                if (isset($lineData['line_id'])) {
                    // Update existing line
                    $line = StockAdjustmentLine::find($lineData['line_id']);
                    if ($line && $line->adjustment_id == $adjustment->adjustment_id) {
                        $line->update([
                            'item_id' => $lineData['item_id'],
                            'warehouse_id' => $lineData['warehouse_id'],
                            'book_quantity' => $lineData['book_quantity'],
                            'adjusted_quantity' => $lineData['adjusted_quantity'],
                            'variance' => $variance
                        ]);
                        
                        $updatedLineIds[] = $line->line_id;
                    }
                } else {
                    // Create new line
                    $line = StockAdjustmentLine::create([
                        'adjustment_id' => $adjustment->adjustment_id,
                        'item_id' => $lineData['item_id'],
                        'warehouse_id' => $lineData['warehouse_id'],
                        'book_quantity' => $lineData['book_quantity'],
                        'adjusted_quantity' => $lineData['adjusted_quantity'],
                        'variance' => $variance
                    ]);
                    
                    $updatedLineIds[] = $line->line_id;
                }
            }
            
            // Delete removed lines
            $linesToDelete = array_diff($currentLineIds, $updatedLineIds);
            if (!empty($linesToDelete)) {
                StockAdjustmentLine::whereIn('line_id', $linesToDelete)->delete();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment updated successfully',
                'data' => $adjustment->load('adjustmentLines.item', 'adjustmentLines.warehouse')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock adjustment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $adjustment = StockAdjustment::find($id);

        if (!$adjustment) {
            return response()->json([
                'success' => false,
                'message' => 'Stock adjustment not found'
            ], 404);
        }

        if ($adjustment->status == StockAdjustment::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete completed adjustments'
            ], 422);
        }

        try {
            $adjustment->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete stock adjustment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit adjustment for approval
     */
    public function submit($id)
    {
        $adjustment = StockAdjustment::find($id);

        if (!$adjustment) {
            return response()->json([
                'success' => false,
                'message' => 'Stock adjustment not found'
            ], 404);
        }

        if ($adjustment->status != StockAdjustment::STATUS_DRAFT) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft adjustments can be submitted'
            ], 422);
        }

        $adjustment->status = StockAdjustment::STATUS_PENDING;
        $adjustment->save();

        return response()->json([
            'success' => true,
            'message' => 'Stock adjustment submitted for approval',
            'data' => $adjustment
        ]);
    }

    /**
     * Approve adjustment
     */
    public function approve($id)
    {
        $adjustment = StockAdjustment::find($id);

        if (!$adjustment) {
            return response()->json([
                'success' => false,
                'message' => 'Stock adjustment not found'
            ], 404);
        }

        if ($adjustment->status != StockAdjustment::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending adjustments can be approved'
            ], 422);
        }

        $adjustment->status = StockAdjustment::STATUS_APPROVED;
        $adjustment->save();

        return response()->json([
            'success' => true,
            'message' => 'Stock adjustment approved',
            'data' => $adjustment
        ]);
    }

    /**
     * Process approved adjustment and update stock
     */
    public function process($id)
    {
        $adjustment = StockAdjustment::find($id);

        if (!$adjustment) {
            return response()->json([
                'success' => false,
                'message' => 'Stock adjustment not found'
            ], 404);
        }

        if ($adjustment->status != StockAdjustment::STATUS_APPROVED) {
            return response()->json([
                'success' => false,
                'message' => 'Only approved adjustments can be processed'
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            foreach ($adjustment->adjustmentLines as $line) {
                if ($line->variance != 0) {
                    // Update ItemStock untuk warehouse tertentu
                    $itemStock = ItemStock::firstOrNew([
                        'item_id' => $line->item_id,
                        'warehouse_id' => $line->warehouse_id
                    ]);
                    
                    if (!$itemStock->exists) {
                        $itemStock->quantity = 0;
                        $itemStock->reserved_quantity = 0;
                    }
                    
                    $itemStock->quantity = $line->adjusted_quantity;
                    $itemStock->save();

                    // ✅ SUDAH AUTO-UPDATE Item.current_stock
                    $item = Item::find($line->item_id);
                    $item->current_stock += $line->adjusted_quantity;
                    $item->save();

                    // Create stock transaction for tracking
                    $moveType = $line->variance > 0 ? 
                        StockTransaction::MOVE_TYPE_IN : 
                        StockTransaction::MOVE_TYPE_OUT;
                    
                    StockTransaction::create([
                        'item_id' => $line->item_id,
                        'warehouse_id' => $line->warehouse_id,
                        'dest_warehouse_id' => null,
                        'transaction_type' => StockTransaction::TYPE_ADJUSTMENT,
                        'move_type' => $moveType,
                        'quantity' => abs($line->variance),
                        'transaction_date' => $adjustment->adjustment_date,
                        'reference_document' => 'stock_adjustment',
                        'reference_number' => $adjustment->adjustment_id,
                        'origin' => 'Stock Adjustment',
                        'state' => StockTransaction::STATE_DONE,
                        'notes' => $adjustment->adjustment_reason
                    ]);
                }
            }

            // Update adjustment status
            $adjustment->status = StockAdjustment::STATUS_COMPLETED;
            $adjustment->save();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment processed successfully',
                'data' => $adjustment->load('adjustmentLines.item', 'adjustmentLines.warehouse')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process stock adjustment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}