<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Models\Contact;
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
        $orders = Order::with(['contact', 'journal.transaction', 'warehouse'])->latest()->paginate(5);

        return new TransactionResource($orders, true, "Successfully fetched orders");
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
        $contact = Contact::where('phone_number', $request->phone_number)->first();

        $request->validate([
            'customer_name' => 'required',
            'phone_type' => 'required',
            'phone_number' => 'required|numeric',
            'address' => 'required',
            'description' => 'required|min:5|max:255',
        ]);

        if (!$contact) {
            $contact = Contact::create([
                'name' => $request->customer_name,
                'type' => 'Customer',
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'description' => 'General Customer',
            ]);
        }
        $order_number = Order::getOrderNumber();
        $order = Order::create([
            'contact_id' => $contact->id,
            'order_number' => $order_number,
            'phone_type' => $request->phone_type,
            'description' => $request->description,
            'warehouse_id' => auth()->user()->role->warehouse_id,
            'user_id' => auth()->user()->id
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
        $order->load(['contact', 'journal', 'transaction.product']);
        return response()->json([
            'order' => $order,
            'message' => 'Successfully fetched order'
        ]); // Gunakan 200 untuk respon sukses "OK"
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
        $order->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
