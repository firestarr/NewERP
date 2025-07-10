<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\PackingList;
use App\Models\Sales\PackingListLine;
use App\Models\Sales\Delivery;
use App\Models\Sales\DeliveryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PackingListController extends Controller
{
    /**
     * Display a listing of the packing lists.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Validate query parameters
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:Draft,In Progress,Completed,Shipped',
            'customer_id' => 'nullable|integer|exists:Customer,customer_id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = PackingList::with(['customer', 'delivery.salesOrder']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('packing_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('packing_date', '<=', $request->date_to);
        }

        $packingLists = $query->orderBy('packing_date', 'desc')->get();

        return response()->json(['data' => $packingLists], 200);
    }

    /**
     * Create packing list from delivery order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createFromDelivery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_id' => 'required|exists:Delivery,delivery_id',
            'packing_date' => 'required|date',
            'packed_by' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $delivery = Delivery::with(['deliveryLines.item', 'customer'])->find($request->delivery_id);

            if (!$delivery) {
                return response()->json(['message' => 'Delivery not found'], 404);
            }

            // Check if packing list already exists for this delivery
            $existingPackingList = PackingList::where('delivery_id', $delivery->delivery_id)->first();
            if ($existingPackingList) {
                return response()->json(['message' => 'Packing list already exists for this delivery'], 400);
            }

            // Create packing list
            $packingList = PackingList::create([
                'packing_list_number' => PackingList::generatePackingListNumber(),
                'packing_date' => $request->packing_date,
                'delivery_id' => $delivery->delivery_id,
                'customer_id' => $delivery->customer_id,
                'status' => PackingList::STATUS_DRAFT,
                'packed_by' => $request->packed_by,
                'notes' => $request->notes
            ]);

            // Create packing list lines from delivery lines
            foreach ($delivery->deliveryLines as $deliveryLine) {
                PackingListLine::create([
                    'packing_list_id' => $packingList->packing_list_id,
                    'delivery_line_id' => $deliveryLine->line_id,
                    'item_id' => $deliveryLine->item_id,
                    'packed_quantity' => 0, // Initially not packed
                    'warehouse_id' => $deliveryLine->warehouse_id,
                    'batch_number' => $deliveryLine->batch_number,
                    'package_number' => 1,
                    'package_type' => 'Box',
                    'weight_per_unit' => $deliveryLine->item->weight ?? 0,
                    'volume_per_unit' => $deliveryLine->item->volume ?? 0
                ]);
            }

            DB::commit();

            return response()->json([
                'data' => $packingList->load(['packingListLines.item', 'delivery', 'customer']),
                'message' => 'Packing list created successfully from delivery'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create packing list', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created packing list in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'packing_date' => 'required|date',
            'delivery_id' => 'required|exists:Delivery,delivery_id',
            'customer_id' => 'required|exists:Customer,customer_id',
            'packed_by' => 'nullable|string|max:100',
            'checked_by' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'packing_lines' => 'required|array',
            'packing_lines.*.delivery_line_id' => 'required|exists:DeliveryLine,line_id',
            'packing_lines.*.item_id' => 'required|exists:Item,item_id',
            'packing_lines.*.packed_quantity' => 'required|numeric|min:0',
            'packing_lines.*.warehouse_id' => 'required|exists:Warehouse,warehouse_id',
            'packing_lines.*.package_number' => 'nullable|integer|min:1',
            'packing_lines.*.package_type' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Create packing list
            $packingList = PackingList::create([
                'packing_list_number' => PackingList::generatePackingListNumber(),
                'packing_date' => $request->packing_date,
                'delivery_id' => $request->delivery_id,
                'customer_id' => $request->customer_id,
                'status' => PackingList::STATUS_DRAFT,
                'packed_by' => $request->packed_by,
                'checked_by' => $request->checked_by,
                'notes' => $request->notes
            ]);

            // Create packing list lines
            $totalWeight = 0;
            $totalVolume = 0;
            $packages = [];

            foreach ($request->packing_lines as $line) {
                $packingListLine = PackingListLine::create([
                    'packing_list_id' => $packingList->packing_list_id,
                    'delivery_line_id' => $line['delivery_line_id'],
                    'item_id' => $line['item_id'],
                    'packed_quantity' => $line['packed_quantity'],
                    'warehouse_id' => $line['warehouse_id'],
                    'batch_number' => $line['batch_number'] ?? null,
                    'package_number' => $line['package_number'] ?? 1,
                    'package_type' => $line['package_type'] ?? 'Box',
                    'weight_per_unit' => $line['weight_per_unit'] ?? 0,
                    'volume_per_unit' => $line['volume_per_unit'] ?? 0,
                    'notes' => $line['notes'] ?? null
                ]);

                $totalWeight += $packingListLine->total_weight;
                $totalVolume += $packingListLine->total_volume;
                $packages[] = $line['package_number'] ?? 1;
            }

            // Update totals
            $packingList->update([
                'total_weight' => $totalWeight,
                'total_volume' => $totalVolume,
                'number_of_packages' => count(array_unique($packages))
            ]);

            DB::commit();

            return response()->json([
                'data' => $packingList->load(['packingListLines.item', 'delivery', 'customer']),
                'message' => 'Packing list created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create packing list', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified packing list.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $packingList = PackingList::with([
            'customer',
            'delivery.salesOrder',
            'packingListLines.item',
            'packingListLines.warehouse',
            'packingListLines.deliveryLine'
        ])->find($id);

        if (!$packingList) {
            return response()->json(['message' => 'Packing list not found'], 404);
        }

        return response()->json(['data' => $packingList], 200);
    }

    /**
     * Update the specified packing list in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $packingList = PackingList::find($id);

        if (!$packingList) {
            return response()->json(['message' => 'Packing list not found'], 404);
        }

        // Check if packing list can be updated
        if ($packingList->status === PackingList::STATUS_SHIPPED) {
            return response()->json(['message' => 'Cannot update a shipped packing list'], 400);
        }

        $validator = Validator::make($request->all(), [
            'packing_date' => 'sometimes|date',
            'packed_by' => 'nullable|string|max:100',
            'checked_by' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'packing_lines' => 'sometimes|array',
            'packing_lines.*.line_id' => 'sometimes|exists:PackingListLine,line_id',
            'packing_lines.*.packed_quantity' => 'required|numeric|min:0',
            'packing_lines.*.package_number' => 'nullable|integer|min:1',
            'packing_lines.*.package_type' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Update packing list header
            $packingList->update($request->only([
                'packing_date', 'packed_by', 'checked_by', 'notes'
            ]));

            // Update packing list lines if provided
            if ($request->has('packing_lines')) {
                $totalWeight = 0;
                $totalVolume = 0;
                $packages = [];

                foreach ($request->packing_lines as $lineData) {
                    if (isset($lineData['line_id'])) {
                        $line = PackingListLine::find($lineData['line_id']);
                        if ($line && $line->packing_list_id == $packingList->packing_list_id) {
                            $line->update([
                                'packed_quantity' => $lineData['packed_quantity'],
                                'package_number' => $lineData['package_number'] ?? $line->package_number,
                                'package_type' => $lineData['package_type'] ?? $line->package_type,
                                'notes' => $lineData['notes'] ?? $line->notes
                            ]);

                            $totalWeight += $line->total_weight;
                            $totalVolume += $line->total_volume;
                            $packages[] = $line->package_number;
                        }
                    }
                }

                // Update totals
                $packingList->update([
                    'total_weight' => $totalWeight,
                    'total_volume' => $totalVolume,
                    'number_of_packages' => count(array_unique($packages))
                ]);
            }

            DB::commit();

            return response()->json([
                'data' => $packingList->load(['packingListLines.item', 'delivery', 'customer']),
                'message' => 'Packing list updated successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update packing list', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified packing list from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $packingList = PackingList::find($id);

        if (!$packingList) {
            return response()->json(['message' => 'Packing list not found'], 404);
        }

        // Check if packing list can be deleted
        if ($packingList->status === PackingList::STATUS_SHIPPED) {
            return response()->json(['message' => 'Cannot delete a shipped packing list'], 400);
        }

        DB::beginTransaction();

        try {
            // Delete packing list lines
            PackingListLine::where('packing_list_id', $packingList->packing_list_id)->delete();
            
            // Delete packing list
            $packingList->delete();

            DB::commit();

            return response()->json(['message' => 'Packing list deleted successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete packing list', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Complete packing process.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function completePacking(Request $request, $id)
    {
        $packingList = PackingList::with('packingListLines')->find($id);

        if (!$packingList) {
            return response()->json(['message' => 'Packing list not found'], 404);
        }

        if ($packingList->status === PackingList::STATUS_COMPLETED) {
            return response()->json(['message' => 'Packing list already completed'], 400);
        }

        $validator = Validator::make($request->all(), [
            'checked_by' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate all lines have packed quantities
        $hasUnpackedItems = $packingList->packingListLines->contains(function ($line) {
            return $line->packed_quantity <= 0;
        });

        if ($hasUnpackedItems) {
            return response()->json(['message' => 'Cannot complete packing - some items are not packed'], 400);
        }

        $packingList->update([
            'status' => PackingList::STATUS_COMPLETED,
            'checked_by' => $request->checked_by
        ]);

        return response()->json([
            'data' => $packingList->fresh(['packingListLines.item', 'delivery', 'customer']),
            'message' => 'Packing completed successfully'
        ], 200);
    }

    /**
     * Mark packing list as shipped.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function markAsShipped($id)
    {
        $packingList = PackingList::find($id);

        if (!$packingList) {
            return response()->json(['message' => 'Packing list not found'], 404);
        }

        if ($packingList->status !== PackingList::STATUS_COMPLETED) {
            return response()->json(['message' => 'Packing list must be completed before shipping'], 400);
        }

        $packingList->update(['status' => PackingList::STATUS_SHIPPED]);

        return response()->json([
            'data' => $packingList->fresh(['packingListLines.item', 'delivery', 'customer']),
            'message' => 'Packing list marked as shipped'
        ], 200);
    }

    /**
     * Get available delivery orders for packing.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAvailableDeliveries()
    {
        // Get deliveries that don't have packing lists yet
        $deliveries = Delivery::with(['customer', 'salesOrder', 'deliveryLines.item'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from(DB::raw('"PackingList"'))
                    ->whereRaw('"PackingList".delivery_id = "Delivery".delivery_id');
            })
            ->whereIn('status', ['Pending', 'In Transit'])
            ->orderBy('delivery_date')
            ->get();

        return response()->json(['data' => $deliveries], 200);
    }

    /**
     * Get packing progress report.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPackingProgress(Request $request)
    {
        $query = PackingList::with(['delivery.salesOrder', 'customer']);

        // Filter by date range
        if ($request->has('date_from') && $request->date_from !== '') {
            $query->where('packing_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->where('packing_date', '<=', $request->date_to);
        }

        $packingLists = $query->get();

        $summary = [
            'total_packing_lists' => $packingLists->count(),
            'draft' => $packingLists->where('status', PackingList::STATUS_DRAFT)->count(),
            'in_progress' => $packingLists->where('status', PackingList::STATUS_IN_PROGRESS)->count(),
            'completed' => $packingLists->where('status', PackingList::STATUS_COMPLETED)->count(),
            'shipped' => $packingLists->where('status', PackingList::STATUS_SHIPPED)->count(),
            'total_packages' => $packingLists->sum('number_of_packages'),
            'total_weight' => $packingLists->sum('total_weight'),
            'total_volume' => $packingLists->sum('total_volume')
        ];

        return response()->json([
            'summary' => $summary,
            'packing_lists' => $packingLists
        ], 200);
    }
}