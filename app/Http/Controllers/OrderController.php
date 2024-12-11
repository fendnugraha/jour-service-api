<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        $user = Auth::user();
        $request->validate([
            'customer_name' => 'required',
            'phone_type' => 'required',
            'phone_number' => 'required|numeric',
            'address' => 'required',
            'description' => 'required|min:5|max:255',
        ]);

        $order_number = Order::getOrderNumber();
        $order = Order::create([
            'customer_name' => $request->customer_name,
            'order_number' => $order_number,
            'phone_type' => $request->phone_type,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'description' => $request->description,
            'warehouse_id' => $user->role->warehouse_id,
            'user_id' => $user->id
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
