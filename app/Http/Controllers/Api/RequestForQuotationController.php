<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RequestForQuotation;
use App\Models\RFQVendor;
use App\Models\PurchaseRequisition;
use App\Models\Vendor;
use App\Models\VendorQuotation;
use App\Http\Requests\RequestForQuotationRequest;
use App\Services\RFQNumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RequestForQuotationController extends Controller
{
    protected $rfqNumberGenerator;
    
    public function __construct(RFQNumberGenerator $rfqNumberGenerator)
    {
        $this->rfqNumberGenerator = $rfqNumberGenerator;
    }
    
    public function index(Request $request)
    {
        $query = RequestForQuotation::with([
            'lines.item', 
            'lines.unitOfMeasure', 
            'selectedVendors',
            'vendorQuotations.vendor'
        ]);
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('rfq_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('rfq_date', '<=', $request->date_to);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('rfq_number', 'like', "%{$search}%");
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'rfq_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $rfqs = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $rfqs
        ]);
    }

    public function store(RequestForQuotationRequest $request)
    {
        try {
            DB::beginTransaction();
            
            // Generate RFQ number
            $rfqNumber = $this->rfqNumberGenerator->generate();
            
            // Create RFQ
            $rfq = RequestForQuotation::create([
                'rfq_number' => $rfqNumber,
                'rfq_date' => $request->rfq_date,
                'validity_date' => $request->validity_date,
                'status' => 'draft',
                'notes' => $request->notes ?? null,
                'reference_document' => $request->reference_document ?? null
            ]);
            
            // Create RFQ lines
            foreach ($request->lines as $line) {
                $rfq->lines()->create([
                    'item_id' => $line['item_id'],
                    'quantity' => $line['quantity'],
                    'uom_id' => $line['uom_id'],
                    'required_date' => $line['required_date'] ?? null
                ]);
            }

            // **PERBAIKAN: Simpan vendor selection jika ada**
            if ($request->has('vendors') && is_array($request->vendors)) {
                foreach ($request->vendors as $vendorId) {
                    $rfq->addVendor($vendorId, 'selected');
                }
            }

            // **PERBAIKAN: Update PR status jika konversi dari PR**
            if ($request->has('pr_id')) {
                $purchaseRequisition = PurchaseRequisition::find($request->pr_id);
                if ($purchaseRequisition) {
                    $purchaseRequisition->update(['status' => 'converted_to_rfq']);
                }
            }
            
            DB::commit();
            
            // Load relationships for response
            $rfq->load(['lines.item', 'lines.unitOfMeasure', 'selectedVendors']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Request For Quotation created successfully',
                'data' => $rfq
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Request For Quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(RequestForQuotation $requestForQuotation)
    {
        $requestForQuotation->load([
            'lines.item', 
            'lines.unitOfMeasure', 
            'selectedVendors',
            'vendorQuotations.vendor',
            'vendorQuotations.lines.item',
            'vendorQuotations.lines.unitOfMeasure'
        ]);
        
        return response()->json([
            'status' => 'success',
            'data' => $requestForQuotation
        ]);
    }

    public function update(RequestForQuotationRequest $request, RequestForQuotation $requestForQuotation)
    {
        // Check if RFQ can be updated (only draft status)
        if ($requestForQuotation->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft RFQs can be updated'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Update RFQ details
            $requestForQuotation->update([
                'rfq_date' => $request->rfq_date,
                'validity_date' => $request->validity_date,
                'notes' => $request->notes ?? $requestForQuotation->notes
            ]);
            
            // Update RFQ lines
            if ($request->has('lines')) {
                // Delete existing lines
                $requestForQuotation->lines()->delete();
                
                // Create new lines
                foreach ($request->lines as $line) {
                    $requestForQuotation->lines()->create([
                        'item_id' => $line['item_id'],
                        'quantity' => $line['quantity'],
                        'uom_id' => $line['uom_id'],
                        'required_date' => $line['required_date'] ?? null
                    ]);
                }
            }

            // **PERBAIKAN: Update vendor selection jika ada**
            if ($request->has('vendors')) {
                // Remove existing vendor selections
                $requestForQuotation->selectedVendors()->detach();
                
                // Add new vendor selections
                foreach ($request->vendors as $vendorId) {
                    $requestForQuotation->addVendor($vendorId, 'selected');
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Request For Quotation updated successfully',
                'data' => $requestForQuotation->load(['lines.item', 'lines.unitOfMeasure', 'selectedVendors'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Request For Quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(RequestForQuotation $requestForQuotation)
    {
        // Check if RFQ can be deleted (only draft status)
        if ($requestForQuotation->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft RFQs can be deleted'
            ], 400);
        }
        
        // Check if RFQ has vendor quotations
        if ($requestForQuotation->vendorQuotations()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'RFQ has vendor quotations and cannot be deleted'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Delete RFQ lines
            $requestForQuotation->lines()->delete();
            
            // **PERBAIKAN: Delete vendor selections**
            $requestForQuotation->selectedVendors()->detach();
            
            // Delete RFQ
            $requestForQuotation->delete();
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Request For Quotation deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete Request For Quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function updateStatus(Request $request, RequestForQuotation $requestForQuotation)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,closed,canceled'
        ]);
        
        // Additional validations based on status transition
        $currentStatus = $requestForQuotation->status;
        $newStatus = $request->status;
        
        $validTransitions = [
            'draft' => ['sent', 'canceled'],
            'sent' => ['closed', 'canceled'],
            'closed' => ['canceled'],
            'canceled' => []
        ];
        
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'status' => 'error',
                'message' => "Status cannot be changed from {$currentStatus} to {$newStatus}"
            ], 400);
        }
        
        $requestForQuotation->update(['status' => $newStatus]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'RFQ status updated successfully',
            'data' => $requestForQuotation
        ]);
    }

    /**
     * **PERBAIKAN: Method baru untuk mendapatkan vendor data untuk RFQ**
     * Get vendors for RFQ with selection status
     */
    public function getVendors($id)
    {
        try {
            $rfq = RequestForQuotation::with('selectedVendors')->findOrFail($id);
            
            // Get all active vendors
            $allVendors = Vendor::where('status', 'active')
                               ->select('vendor_id', 'vendor_code', 'name', 'email', 'contact_person', 'phone')
                               ->orderBy('name')
                               ->get();
            
            // Get selected vendors with pivot data
            $selectedVendors = $rfq->selectedVendors;
            $selectedVendorIds = $selectedVendors->pluck('vendor_id')->toArray();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'all_vendors' => $allVendors,
                    'selected_vendors' => $selectedVendors,
                    'selected_vendor_ids' => $selectedVendorIds,
                    'selection_count' => count($selectedVendorIds)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get vendor data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * **PERBAIKAN: Method baru untuk update vendor selection**
     * Update selected vendors for RFQ
     */
    public function updateVendors(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'vendor_ids' => 'required|array|min:1',
            'vendor_ids.*' => 'integer|exists:vendors,vendor_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $rfq = RequestForQuotation::findOrFail($id);
            
            // Check if RFQ is in draft status
            if ($rfq->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Can only update vendors for RFQ in draft status'
                ], 400);
            }
            
            // Remove all existing vendor selections
            $rfq->selectedVendors()->detach();
            
            // Add new vendor selections
            foreach ($request->vendor_ids as $vendorId) {
                $rfq->addVendor($vendorId, 'selected');
            }
            
            DB::commit();
            
            // Reload the relationship
            $rfq->load('selectedVendors');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Vendor selection updated successfully',
                'data' => [
                    'selected_vendors' => $rfq->selectedVendors,
                    'count' => $rfq->selectedVendors->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update vendor selection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * **PERBAIKAN: Method baru untuk mark vendors as sent**
     * Mark vendors as sent when RFQ is sent
     */
    public function markVendorsAsSent(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'vendor_ids' => 'required|array|min:1',
            'vendor_ids.*' => 'integer|exists:vendors,vendor_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $rfq = RequestForQuotation::findOrFail($id);
            
            // Update vendor status to 'sent'
            $updated = $rfq->rfqVendors()
                          ->whereIn('vendor_id', $request->vendor_ids)
                          ->update([
                              'status' => 'sent',
                              'sent_at' => now()
                          ]);
            
            return response()->json([
                'status' => 'success',
                'message' => "Marked {$updated} vendors as sent",
                'data' => [
                    'updated_count' => $updated
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark vendors as sent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * **PERBAIKAN: Method baru untuk menambah vendor**
     * Add vendor to RFQ
     */
    public function addVendor(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|integer|exists:vendors,vendor_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $rfq = RequestForQuotation::findOrFail($id);
            
            // Check if vendor already selected
            $exists = $rfq->selectedVendors()->where('vendor_id', $request->vendor_id)->exists();
            
            if ($exists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vendor already selected for this RFQ'
                ], 400);
            }
            
            // Add vendor
            $rfq->addVendor($request->vendor_id, 'selected');
            
            // Reload and return updated data
            $rfq->load('selectedVendors');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Vendor added successfully',
                'data' => $rfq->selectedVendors
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add vendor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * **PERBAIKAN: Method baru untuk remove vendor**
     * Remove vendor from RFQ
     */
    public function removeVendor(Request $request, $id, $vendorId)
    {
        try {
            $rfq = RequestForQuotation::findOrFail($id);
            
            // Check if RFQ is in draft status
            if ($rfq->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Can only remove vendors from RFQ in draft status'
                ], 400);
            }
            
            // Remove vendor
            $removed = $rfq->selectedVendors()->detach($vendorId);
            
            if ($removed) {
                // Reload and return updated data
                $rfq->load('selectedVendors');
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Vendor removed successfully',
                    'data' => $rfq->selectedVendors
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vendor not found in RFQ selection'
                ], 404);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove vendor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available vendors for creating vendor quotation (vendors that haven't submitted quotation for this RFQ)
     */
    public function getAvailableVendors(Request $request, $rfqId)
    {
        try {
            // Check if RFQ exists
            $rfq = RequestForQuotation::find($rfqId);
            if (!$rfq) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request For Quotation not found'
                ], 404);
            }

            // Check if RFQ is in 'sent' status (only 'sent' RFQs can have vendor quotations)
            if ($rfq->status !== 'sent') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vendor quotations can only be created for RFQs with status "sent"',
                    'data' => []
                ], 400);
            }

            // Get vendor IDs that already have quotations for this RFQ
            $vendorsWithQuotations = VendorQuotation::where('rfq_id', $rfqId)
                ->pluck('vendor_id')
                ->toArray();

            // **PERBAIKAN: Prioritaskan vendor yang sudah dipilih saat konversi**
            // Get selected vendors for this RFQ
            $selectedVendors = $rfq->selectedVendors()->pluck('vendor_id')->toArray();

            // Get all active vendors that don't have quotations for this RFQ yet
            $availableVendors = Vendor::where('status', 'active')
                ->whereNotIn('vendor_id', $vendorsWithQuotations)
                ->select('vendor_id', 'vendor_code', 'name', 'contact_person', 'email', 'phone', 'preferred_currency')
                ->orderByRaw('CASE WHEN vendor_id IN (' . implode(',', $selectedVendors ?: [0]) . ') THEN 0 ELSE 1 END')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Available vendors retrieved successfully',
                'data' => [
                    'rfq' => [
                        'rfq_id' => $rfq->rfq_id,
                        'rfq_number' => $rfq->rfq_number,
                        'status' => $rfq->status,
                        'rfq_date' => $rfq->rfq_date,
                        'validity_date' => $rfq->validity_date
                    ],
                    'vendors' => $availableVendors,
                    'selected_vendors' => $selectedVendors,
                    'vendors_with_quotations_count' => count($vendorsWithQuotations),
                    'available_vendors_count' => $availableVendors->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve available vendors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vendors that already submitted quotations for this RFQ
     */
    public function getVendorsWithQuotations($rfqId)
    {
        try {
            $rfq = RequestForQuotation::find($rfqId);
            if (!$rfq) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request For Quotation not found'
                ], 404);
            }

            // Get vendors with their quotations for this RFQ
            $vendorsWithQuotations = VendorQuotation::with(['vendor'])
                ->where('rfq_id', $rfqId)
                ->get()
                ->map(function($quotation) {
                    return [
                        'quotation_id' => $quotation->quotation_id,
                        'vendor_id' => $quotation->vendor_id,
                        'vendor_code' => $quotation->vendor->vendor_code,
                        'vendor_name' => $quotation->vendor->name,
                        'quotation_date' => $quotation->quotation_date,
                        'validity_date' => $quotation->validity_date,
                        'status' => $quotation->status,
                        'currency_code' => $quotation->currency_code,
                        'total_amount' => $quotation->total_amount
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'rfq' => [
                        'rfq_id' => $rfq->rfq_id,
                        'rfq_number' => $rfq->rfq_number,
                        'status' => $rfq->status
                    ],
                    'vendors_with_quotations' => $vendorsWithQuotations,
                    'count' => $vendorsWithQuotations->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve vendors with quotations',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}