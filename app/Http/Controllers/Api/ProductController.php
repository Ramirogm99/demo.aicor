<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function getProducts(Request $request)
    {
        if ($request->category == []) {
            $products = Product::all();
        } else {
            $category = $request->category;
            $products = Product::whereIn('category_id', $category)->get();
        }
        return response()->json($products);
    }

}
