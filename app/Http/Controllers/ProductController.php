<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = Product::where('name', 'like', '%' . $request->search . '%')->paginate(5)->onEachSide(0);
        return new ProductResource($products, true, "Successfully fetched products");
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $product = new Product();
        $request->validate(
            [
                'name' => 'required|string|max:255|unique:products,name',
                'category' => 'required',  // Make sure category_id is present
                'price' => 'required|numeric',
                'cost' => 'required|numeric',
            ]
        );

        $product->create([
            'code' => $product->newCode($request->category),
            'name' => $request->name,
            'category' => $request->category,
            'price' => $request->price,
            'cost' => $request->cost
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
