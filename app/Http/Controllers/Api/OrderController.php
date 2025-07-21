<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function getOlderOrders(Request $request)
    {
        $orders = Order::where('user_id', $request->user_id)->with('orderItems.product')->get();
        return response()->json($orders);
    }
}
