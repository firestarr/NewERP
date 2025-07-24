<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\SystemCurrency;
use Illuminate\Http\Request;

class SystemCurrencyController extends Controller
{
    /**
     * Return list of system currencies.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $currencies = SystemCurrency::all();
        return response()->json(['data' => $currencies], 200);
    }
}
