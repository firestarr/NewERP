<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Manufacturing\ProductionOrder;
use App\Models\Manufacturing\ProductionConsumption;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductionConsumptionController extends Controller
{
    /**
     * Display a listing of the resource for a specific production order.
     *
     * @param  int  $productionId
     * @return \Illuminate\Http\Response
     */
    public function index($productionId)
    {
        $productionOrder = ProductionOrder::find($productionId);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        $consumptions = ProductionConsumption::with(['item', 'warehouse'])
            ->where('production_id', $productionId)
            ->get();

        return response()->json([
            'data' => $consumptions,
            'production_order_status' => $productionOrder->status,
            'can_edit' => $productionOrder->status === 'Draft'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $productionId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $productionId)
    {
        $productionOrder = ProductionOrder::find($productionId);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        // Check if production order can be modified
        if (!$this->canModifyConsumption($productionOrder)) {
            return response()->json([
                'message' => 'Cannot modify consumptions',
                'errors' => ['status' => ['Consumptions can only be modified when production order is in Draft status']]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer|exists:items,item_id',
            'planned_quantity' => 'required|numeric|min:0',
            'actual_quantity' => 'sometimes|nullable|numeric|min:0',
            'warehouse_id' => 'required|integer|exists:warehouses,warehouse_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plannedQty = $request->planned_quantity;
        $actualQty = $request->actual_quantity ?? 0;

        DB::beginTransaction();
        try {
            $consumption = new ProductionConsumption();
            $consumption->production_id = $productionId;
            $consumption->item_id = $request->item_id;
            $consumption->planned_quantity = $plannedQty;
            $consumption->actual_quantity = $actualQty;
            $consumption->variance = $plannedQty - $actualQty;
            $consumption->warehouse_id = $request->warehouse_id;
            $consumption->save();

            DB::commit();

            return response()->json([
                'data' => $consumption->load(['item', 'warehouse']),
                'message' => 'Production consumption created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create consumption',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $productionId
     * @param  int  $consumptionId
     * @return \Illuminate\Http\Response
     */
    public function show($productionId, $consumptionId)
    {
        $consumption = ProductionConsumption::with(['item', 'warehouse'])
            ->where('production_id', $productionId)
            ->where('consumption_id', $consumptionId)
            ->first();

        if (!$consumption) {
            return response()->json(['message' => 'Production consumption not found'], 404);
        }

        return response()->json(['data' => $consumption]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $productionId
     * @param  int  $consumptionId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $productionId, $consumptionId)
    {
        $productionOrder = ProductionOrder::find($productionId);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        $consumption = ProductionConsumption::where('production_id', $productionId)
            ->where('consumption_id', $consumptionId)
            ->first();

        if (!$consumption) {
            return response()->json(['message' => 'Production consumption not found'], 404);
        }

        // Check if production order allows modification
        if (!$this->canModifyConsumption($productionOrder, $request)) {
            return response()->json([
                'message' => 'Cannot modify consumption',
                'errors' => ['status' => [$this->getModificationErrorMessage($productionOrder, $request)]]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'sometimes|required|integer|exists:items,item_id',
            'planned_quantity' => 'sometimes|required|numeric|min:0',
            'actual_quantity' => 'sometimes|nullable|numeric|min:0',
            'warehouse_id' => 'sometimes|required|integer|exists:warehouses,warehouse_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Update the consumption with the provided data
            $originalData = $consumption->toArray();
            $consumption->fill($request->except('variance'));

            // Calculate variance if needed
            if ($request->has('planned_quantity') || $request->has('actual_quantity')) {
                $plannedQty = $request->has('planned_quantity') ? $request->planned_quantity : $consumption->planned_quantity;
                $actualQty = $request->has('actual_quantity') ? $request->actual_quantity : $consumption->actual_quantity;
                $consumption->variance = $plannedQty - $actualQty;
            }

            $consumption->save();

            DB::commit();

            return response()->json([
                'data' => $consumption->load(['item', 'warehouse']),
                'message' => 'Production consumption updated successfully',
                'changes' => $this->getChanges($originalData, $consumption->toArray())
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update consumption',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $productionId
     * @param  int  $consumptionId
     * @return \Illuminate\Http\Response
     */
    public function destroy($productionId, $consumptionId)
    {
        $productionOrder = ProductionOrder::find($productionId);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        // Check if production order can be modified
        if (!$this->canModifyConsumption($productionOrder)) {
            return response()->json([
                'message' => 'Cannot delete consumption',
                'errors' => ['status' => ['Consumptions can only be deleted when production order is in Draft status']]
            ], 422);
        }

        $consumption = ProductionConsumption::where('production_id', $productionId)
            ->where('consumption_id', $consumptionId)
            ->first();

        if (!$consumption) {
            return response()->json(['message' => 'Production consumption not found'], 404);
        }

        DB::beginTransaction();
        try {
            $consumption->delete();
            DB::commit();

            return response()->json(['message' => 'Production consumption deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete consumption',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update consumptions for a production order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $productionId
     * @return \Illuminate\Http\Response
     */
    public function bulkUpdate(Request $request, $productionId)
    {
        $productionOrder = ProductionOrder::find($productionId);

        if (!$productionOrder) {
            return response()->json(['message' => 'Production order not found'], 404);
        }

        // Check if production order allows modification
        if (!$this->canModifyConsumption($productionOrder, $request)) {
            return response()->json([
                'message' => 'Cannot modify consumptions',
                'errors' => ['status' => [$this->getModificationErrorMessage($productionOrder, $request)]]
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'consumptions' => 'required|array|min:1',
            'consumptions.*.consumption_id' => 'required|integer',
            'consumptions.*.actual_quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $updatedConsumptions = [];

            foreach ($request->consumptions as $consumptionData) {
                $consumption = ProductionConsumption::where('production_id', $productionId)
                    ->where('consumption_id', $consumptionData['consumption_id'])
                    ->first();

                if ($consumption) {
                    $consumption->actual_quantity = $consumptionData['actual_quantity'];
                    $consumption->variance = $consumption->planned_quantity - $consumptionData['actual_quantity'];
                    $consumption->save();

                    $updatedConsumptions[] = $consumption->load(['item', 'warehouse']);
                }
            }

            DB::commit();

            return response()->json([
                'data' => $updatedConsumptions,
                'message' => 'Consumptions updated successfully',
                'count' => count($updatedConsumptions)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update consumptions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if consumption can be modified based on production order status.
     *
     * @param  ProductionOrder  $productionOrder
     * @param  Request|null  $request
     * @return bool
     */
    private function canModifyConsumption(ProductionOrder $productionOrder, Request $request = null)
    {
        // Only allow modifications when production order is in Draft status
        if ($productionOrder->status === 'Draft') {
            return true;
        }

        // Special case: Allow actual_quantity updates when status is 'Materials Issued'
        // (for issuing materials workflow)
        if ($productionOrder->status === 'Materials Issued' && $request) {
            // Only allow actual_quantity updates, not other fields
            $allowedFields = ['actual_quantity'];
            $requestFields = array_keys($request->except(['_method', '_token']));

            return count(array_diff($requestFields, $allowedFields)) === 0;
        }

        return false;
    }

    /**
     * Get appropriate error message for modification restrictions.
     *
     * @param  ProductionOrder  $productionOrder
     * @param  Request|null  $request
     * @return string
     */
    private function getModificationErrorMessage(ProductionOrder $productionOrder, Request $request = null)
    {
        switch ($productionOrder->status) {
            case 'Materials Issued':
                return 'Only actual quantities can be modified when materials are issued';
            case 'In Progress':
                return 'Consumptions cannot be modified when production is in progress';
            case 'Completed':
                return 'Consumptions cannot be modified for completed production orders';
            case 'Cancelled':
                return 'Consumptions cannot be modified for cancelled production orders';
            default:
                return 'Consumptions can only be modified when production order is in Draft status';
        }
    }

    /**
     * Get changes between original and updated data.
     *
     * @param  array  $original
     * @param  array  $updated
     * @return array
     */
    private function getChanges(array $original, array $updated)
    {
        $changes = [];
        $trackFields = ['planned_quantity', 'actual_quantity', 'variance', 'warehouse_id', 'item_id'];

        foreach ($trackFields as $field) {
            if (isset($original[$field]) && isset($updated[$field]) && $original[$field] != $updated[$field]) {
                $changes[$field] = [
                    'from' => $original[$field],
                    'to' => $updated[$field]
                ];
            }
        }

        return $changes;
    }
}
