<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function paymentDoneOrder(Request $request)
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'userdata' => 'required|array',
        ]);
        $dataarray = json_decode($request->getContent(), true);
        $order = new Order();
        $user = $this->userOrder($dataarray["userdata"]);
        $order->user_id = $user->id;
        $cartItems = $dataarray["cart"];
        $order->total_price = 0;
        $order->save();
        foreach ($cartItems as $item) {
            $product = Product::where('id', $item[0]["id"])->first();
            $order->total_price += $item[0]['quantity'] * $product->price;
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item[0]['id'],
                'quantity' => $item[0]['quantity'],
                'price' => $product->price,
            ])->save();
            $productBought = Product::find($item[0]['id']);
            if ($productBought->stock < $item[0]['quantity']) {
                return response()->json(['error' => 'Insufficient stock for product: ' . $productBought->name], 400);
            }
            $productBought->stock -= $item[0]['quantity'];
            $productBought->save();
        }
        $order->save();
        return response()->json(['success' => 'Order placed successfully'], 200);
    }
    public function getOlderOrders(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        $orders = Order::where('user_id', $user->id)->with('orderItems.product')->get();
        return response()->json($orders);
    }
    private function userOrder($data)
    {
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('password'),
            ]);
        }
        return ($user);
    }
}
