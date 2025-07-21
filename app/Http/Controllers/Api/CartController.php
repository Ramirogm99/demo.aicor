<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function getCart(Request $request)
    {
        $cart = Cart::where("user_id", $request->user_id)->first();
        if (!$cart) {
            $cart = new Cart();
            $cart->user_id = $request->user_id;
            $cart->save();
        }
        return response()->json(Cart::with('cartItems')->find($cart->id));
    }
    public function updateCart(Request $request)
    {
        dd($request->json()->all());
        $cart = Cart::find($request->cart_head)->with('cartItems')->first();
        $cart->cartItems()->delete();

        foreach ($request->cart_items as $item) {
            $product_price = Product::find($item['product_id'])->price;
            if (!$product_price) {
                return response()->json(['error' => 'Product not found'], 404);
            }
            $cart->cartItems()->create([
                "cart_id" => $cart->id,
                "product_id" => $item['product_id'],
                "quantity" => $item['quantity'],
                "price" => $product_price
            ]);
            $cart->total_amount += $product_price * $item['quantity'];
        }
        $cart->save();
        return response()->json(['message' => 'Cart updated successfully']);
    }
    public function deleteCart(Request $request)
    {
        dd($request->all());
        $cartItems = CartItem::where('cart_id', $request->id)->delete();
        return response()->json(['message' => 'Cart deleted successfully']);
    }
    public function paymentDoneCart(Request $request)
    {
        dd($request->all());
        $cart = Cart::where('user_id', $request->id)->first();
        $order = new Order();
        $order->user_id = $cart->user_id;
        $order->total_amount = $cart->total_amount;
        $cartItems = CartItem::where('cart_id', $cart->id)->get();
        foreach ($cartItems as $item) {
            $order->orderItems()->attach($item->id, ['quantity' => $item->quantity]);
            $productBought = Product::find($item->product_id);
            if ($productBought->stock < $item->quantity) {
                return response()->json(['error' => 'Insufficient stock for product: ' . $productBought->name], 400);
            }
            $productBought->stock -= $item->quantity;
            $productBought->save();
        }
        $order->save();
        $cart->delete();

    }
}
