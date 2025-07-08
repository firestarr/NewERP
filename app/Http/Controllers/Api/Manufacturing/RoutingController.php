<?php

namespace App\Http\Controllers\Api\Manufacturing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Manufacturing\Routing;
use App\Models\Manufacturing\RoutingOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class RoutingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Routing::with('item');

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('routing_code', 'like', '%' . $search . '%')
                        ->orWhere('revision', 'like', '%' . $search . '%')
                        ->orWhereHas('item', function ($itemQuery) use ($search) {
                            $itemQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('item_code', 'like', '%' . $search . '%');
                        });
                });
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'routing_code');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 10);
            $routings = $query->paginate($perPage);

            return response()->json([
                'data' => $routings->items(),
                'meta' => [
                    'current_page' => $routings->currentPage(),
                    'last_page' => $routings->lastPage(),
                    'per_page' => $routings->perPage(),
                    'from' => $routings->firstItem(),
                    'to' => $routings->lastItem(),
                    'total' => $routings->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch routings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $routing = Routing::with('item')->find($id);

            if (!$routing) {
                return response()->json(['message' => 'Routing not found'], 404);
            }

            return response()->json(['data' => $routing]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch routing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'item_id' => 'required|integer|exists:items,item_id',
                'routing_code' => 'required|string|max:20|unique:routings,routing_code',
                'revision' => 'required|string|max:10',
                'effective_date' => 'required|date',
                'status' => 'required|in:Active,Inactive',
                'cavity' => 'nullable|integer|min:1',
                'process' => 'nullable|string|max:255',
                'set_jump' => 'nullable|numeric|min:0',
                'operations' => 'array',
                'operations.*.workcenter_id' => 'required|integer|exists:work_centers,workcenter_id',
                'operations.*.operation_name' => 'required|string|max:100',
                'operations.*.work_flow' => 'nullable|string|max:100',
                'operations.*.models' => 'nullable|string|max:100',
                'operations.*.sequence' => 'required|integer',
                'operations.*.setup_time' => 'required|numeric',
                'operations.*.run_time' => 'required|numeric',
                'operations.*.uom_id' => 'required|integer|exists:unit_of_measures,uom_id',
                'operations.*.labor_cost' => 'required|numeric',
                'operations.*.overhead_cost' => 'required|numeric',
                'yield' => 'nullable|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $routing = Routing::create([
                'item_id' => $request->item_id,
                'routing_code' => $request->routing_code,
                'revision' => $request->revision,
                'effective_date' => $request->effective_date,
                'status' => $request->status,
                'cavity' => $request->cavity,
                'process' => $request->process,
                'set_jump' => $request->set_jump,
                'yield' => $request->yield,
            ]);

            if ($request->has('operations')) {
                foreach ($request->operations as $operation) {
                    RoutingOperation::create([
                        'routing_id' => $routing->routing_id,
                        'workcenter_id' => $operation['workcenter_id'],
                        'operation_name' => $operation['operation_name'],
                        'work_flow' => $operation['work_flow'] ?? null,
                        'models' => $operation['models'] ?? null,
                        // Field existing
                        'sequence' => $operation['sequence'],
                        'setup_time' => $operation['setup_time'],
                        'run_time' => $operation['run_time'],
                        'uom_id' => $operation['uom_id'],
                        'labor_cost' => $operation['labor_cost'],
                        'overhead_cost' => $operation['overhead_cost'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'data' => $routing->load('routingOperations'),
                'message' => 'Routing created successfully'
            ], 201);
        } catch (QueryException $e) {
            DB::rollBack();

            // Handle specific database errors
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Database constraint violation',
                    'error' => 'Duplicate entry or foreign key constraint failed'
                ], 422);
            }

            return response()->json([
                'message' => 'Database error occurred',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create routing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $routing = Routing::find($id);

            if (!$routing) {
                return response()->json(['message' => 'Routing not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'item_id' => 'required|integer|exists:items,item_id',
                'routing_code' => 'required|string|max:20|unique:routings,routing_code,' . $id . ',routing_id',
                'revision' => 'required|string|max:10',
                'effective_date' => 'required|date',
                'status' => 'required|in:Active,Inactive',
                'cavity' => 'nullable|integer|min:1',
                'process' => 'nullable|string|max:255',
                'set_jump' => 'nullable|numeric|min:0',
                'operations' => 'array',
                'operations.*.workcenter_id' => 'required|integer|exists:work_centers,workcenter_id',
                'operations.*.operation_name' => 'required|string|max:100',
                'operations.*.work_flow' => 'nullable|string|max:100',
                'operations.*.models' => 'nullable|string|max:100',
                // Field existing
                'operations.*.sequence' => 'required|integer',
                'operations.*.setup_time' => 'required|numeric',
                'operations.*.run_time' => 'required|numeric',
                'operations.*.uom_id' => 'required|integer|exists:unit_of_measures,uom_id',
                'operations.*.labor_cost' => 'required|numeric',
                'operations.*.overhead_cost' => 'required|numeric',
                'yield' => 'nullable|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $routing->update([
                'item_id' => $request->item_id,
                'routing_code' => $request->routing_code,
                'revision' => $request->revision,
                'effective_date' => $request->effective_date,
                'status' => $request->status,
                'cavity' => $request->cavity,
                'process' => $request->process,
                'set_jump' => $request->set_jump,
                'yield' => $request->yield,
            ]);

            // Delete existing operations and recreate
            if ($request->has('operations')) {
                $routing->routingOperations()->delete();

                foreach ($request->operations as $operation) {
                    RoutingOperation::create([
                        'routing_id' => $routing->routing_id,
                        'workcenter_id' => $operation['workcenter_id'],
                        'operation_name' => $operation['operation_name'],
                        'work_flow' => $operation['work_flow'] ?? null,
                        'models' => $operation['models'] ?? null,
                        // Field existing
                        'sequence' => $operation['sequence'],
                        'setup_time' => $operation['setup_time'],
                        'run_time' => $operation['run_time'],
                        'uom_id' => $operation['uom_id'],
                        'labor_cost' => $operation['labor_cost'],
                        'overhead_cost' => $operation['overhead_cost'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'data' => $routing->load('routingOperations'),
                'message' => 'Routing updated successfully'
            ]);
        } catch (QueryException $e) {
            DB::rollBack();

            // Handle specific database errors
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Database constraint violation',
                    'error' => 'Duplicate entry or foreign key constraint failed'
                ], 422);
            }

            return response()->json([
                'message' => 'Database error occurred',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update routing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $routing = Routing::find($id);

            if (!$routing) {
                return response()->json(['message' => 'Routing not found'], 404);
            }

            DB::beginTransaction();

            // Delete related operations first
            $routing->routingOperations()->delete();

            // Delete the routing
            $routing->delete();

            DB::commit();

            return response()->json(['message' => 'Routing deleted successfully']);
        } catch (QueryException $e) {
            DB::rollBack();

            // Handle foreign key constraint errors
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Cannot delete routing',
                    'error' => 'This routing is being used by other records'
                ], 422);
            }

            return response()->json([
                'message' => 'Database error occurred',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete routing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get routing operations with new fields included
     */
    public function getOperations($routingId)
    {
        try {
            $routing = Routing::find($routingId);

            if (!$routing) {
                return response()->json(['message' => 'Routing not found'], 404);
            }

            $operations = RoutingOperation::with(['workCenter', 'unitOfMeasure'])
                ->where('routing_id', $routingId)
                ->orderBy('sequence')
                ->get()
                ->map(function ($operation) {
                    return [
                        'operation_id' => $operation->operation_id,
                        'routing_id' => $operation->routing_id,
                        'workcenter_id' => $operation->workcenter_id,
                        'operation_name' => $operation->operation_name,
                        'work_flow' => $operation->work_flow,
                        'models' => $operation->models,
                        // Field baru
                        'cavity' => $operation->cavity,
                        'process' => $operation->process,
                        'set_jump' => $operation->set_jump,
                        // Field existing
                        'sequence' => $operation->sequence,
                        'setup_time' => $operation->setup_time,
                        'run_time' => $operation->run_time,
                        'total_time' => $operation->total_time,
                        'uom_id' => $operation->uom_id,
                        'labor_cost' => $operation->labor_cost,
                        'overhead_cost' => $operation->overhead_cost,
                        'work_center' => $operation->workCenter,
                        'unit_of_measure' => $operation->unitOfMeasure,
                    ];
                });

            return response()->json(['data' => $operations]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch operations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate operation data with new fields
     */
    private function validateOperationData($operations)
    {
        $rules = [];

        foreach ($operations as $index => $operation) {
            $rules["operations.{$index}.workcenter_id"] = 'required|integer|exists:work_centers,workcenter_id';
            $rules["operations.{$index}.operation_name"] = 'required|string|max:100';
            $rules["operations.{$index}.work_flow"] = 'nullable|string|max:100';
            $rules["operations.{$index}.models"] = 'nullable|string|max:100';
            // Field baru
            $rules["operations.{$index}.cavity"] = 'nullable|integer|min:1';
            $rules["operations.{$index}.process"] = 'nullable|string|max:255';
            $rules["operations.{$index}.set_jump"] = 'nullable|numeric|min:0';
            // Field existing
            $rules["operations.{$index}.sequence"] = 'required|integer';
            $rules["operations.{$index}.setup_time"] = 'required|numeric|min:0';
            $rules["operations.{$index}.run_time"] = 'required|numeric|min:0';
            $rules["operations.{$index}.uom_id"] = 'required|integer|exists:unit_of_measures,uom_id';
            $rules["operations.{$index}.labor_cost"] = 'required|numeric|min:0';
            $rules["operations.{$index}.overhead_cost"] = 'required|numeric|min:0';
        }

        return $rules;
    }
}
