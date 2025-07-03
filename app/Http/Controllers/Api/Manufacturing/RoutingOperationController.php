<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Manufacturing\Routing;
use App\Models\Manufacturing\RoutingOperation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class RoutingOperationController extends Controller
{
    /**
     * Display a listing of the resource for a specific routing.
     *
     * @param  int  $routingId
     * @return \Illuminate\Http\Response
     */
    public function index($routingId)
    {
        $routing = Routing::find($routingId);

        if (!$routing) {
            return response()->json(['message' => 'Routing not found'], 404);
        }

        $operations = RoutingOperation::with(['workCenter', 'unitOfMeasure'])
            ->where('routing_id', $routingId)
            ->orderBy('sequence')
            ->get();

        return response()->json(['data' => $operations]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $routingId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $routingId)
    {
        $routing = Routing::find($routingId);

        if (!$routing) {
            return response()->json(['message' => 'Routing not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'workcenter_id' => 'required|integer|exists:work_centers,workcenter_id',
            'operation_name' => 'required|string|max:100',
            'work_flow' => 'nullable|string|max:100',
            'models' => 'nullable|string|max:100',
            'sequence' => 'required|integer',
            'setup_time' => 'required|numeric',
            'run_time' => 'required|numeric',
            'uom_id' => 'required|integer|exists:unit_of_measures,uom_id',
            'labor_cost' => 'required|numeric',
            'overhead_cost' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $operation = new RoutingOperation();
        $operation->routing_id = $routingId;
        $operation->workcenter_id = $request->workcenter_id;
        $operation->operation_name = $request->operation_name;
        $operation->work_flow = $request->work_flow;
        $operation->models = $request->models;
        $operation->sequence = $request->sequence;
        $operation->setup_time = $request->setup_time;
        $operation->run_time = $request->run_time;
        $operation->uom_id = $request->uom_id;
        $operation->labor_cost = $request->labor_cost;
        $operation->overhead_cost = $request->overhead_cost;
        $operation->save();

        return response()->json([
            'data' => $operation->load(['workCenter', 'unitOfMeasure']),
            'message' => 'Operation created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $routingId
     * @param  int  $operationId
     * @return \Illuminate\Http\Response
     */
    public function show($routingId, $operationId)
    {
        $routing = Routing::find($routingId);

        if (!$routing) {
            return response()->json(['message' => 'Routing not found'], 404);
        }

        $operation = RoutingOperation::with(['workCenter', 'unitOfMeasure'])
            ->where('routing_id', $routingId)
            ->where('operation_id', $operationId)
            ->first();

        if (!$operation) {
            return response()->json(['message' => 'Operation not found'], 404);
        }

        return response()->json(['data' => $operation]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $routingId
     * @param  int  $operationId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $routingId, $operationId)
    {
        $routing = Routing::find($routingId);

        if (!$routing) {
            return response()->json(['message' => 'Routing not found'], 404);
        }

        $operation = RoutingOperation::where('routing_id', $routingId)
            ->where('operation_id', $operationId)
            ->first();

        if (!$operation) {
            return response()->json(['message' => 'Operation not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'workcenter_id' => 'required|integer|exists:work_centers,workcenter_id',
            'operation_name' => 'required|string|max:100',
            'work_flow' => 'nullable|string|max:100',
            'models' => 'nullable|string|max:100',
            'sequence' => 'required|integer',
            'setup_time' => 'required|numeric',
            'run_time' => 'required|numeric',
            'uom_id' => 'required|integer|exists:unit_of_measures,uom_id',
            'labor_cost' => 'required|numeric',
            'overhead_cost' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $operation->workcenter_id = $request->workcenter_id;
        $operation->operation_name = $request->operation_name;
        $operation->work_flow = $request->work_flow;
        $operation->models = $request->models;
        $operation->sequence = $request->sequence;
        $operation->setup_time = $request->setup_time;
        $operation->run_time = $request->run_time;
        $operation->uom_id = $request->uom_id;
        $operation->labor_cost = $request->labor_cost;
        $operation->overhead_cost = $request->overhead_cost;
        $operation->save();

        return response()->json([
            'data' => $operation->load(['workCenter', 'unitOfMeasure']),
            'message' => 'Operation updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $routingId
     * @param  int  $operationId
     * @return \Illuminate\Http\Response
     */
    public function destroy($routingId, $operationId)
    {
        $routing = Routing::find($routingId);

        if (!$routing) {
            return response()->json(['message' => 'Routing not found'], 404);
        }

        $operation = RoutingOperation::where('routing_id', $routingId)
            ->where('operation_id', $operationId)
            ->first();

        if (!$operation) {
            return response()->json(['message' => 'Operation not found'], 404);
        }

        $operation->delete();

        return response()->json(['message' => 'Operation deleted successfully']);
    }
}
