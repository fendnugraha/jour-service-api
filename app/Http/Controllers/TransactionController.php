<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Journal;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    private function addToJournal($invoice_num, $debt, $cred, $amount, $description = 'Penjualan Barang', $serial = null, $rcv = null, $user_id, $warehouse_id)
    {
        $journal = new Journal([
            'date_issued' => now(),
            'invoice' => $invoice_num, // Ensure $invoice is defined
            'debt_code' => $debt,
            'cred_code' => $cred,
            'amount' => $amount, // Ensure $jual is defined
            'fee_amount' => 0, // Ensure $fee is defined
            'description' => $description,
            'trx_type' => 'Sales',
            'rcv_pay' => $rcv,
            'payment_status' => $rcv ? 0 : null,
            'payment_nth' => $rcv ? 0 : null,
            'user_id' => $user_id ?? auth()->user()->id,
            'warehouse_id' => $warehouse_id ?? auth()->user()->role->warehouse_id,
            'serial_number' => $serial,
        ]);

        $journal->save();

        return $journal;
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //
    }

    public function addToReceivable($invoice, $account, $amount, $description, $user, $dateIssued, $dueDate, $contact_id)
    {
        DB::beginTransaction();
        try {
            Receivable::create([
                'date_issued' => $dateIssued,
                'due_date' => Carbon::parse($dateIssued)->addDays($dueDate),
                'invoice' => $invoice,
                'description' => $description ?? 'Piutang Usaha',
                'bill_amount' => $amount,
                'payment_amount' => 0,
                'payment_status' => 0,
                'payment_nth' => 0,
                'contact_id' => $contact_id,
                'user_id' => $user->id,
                'account_code' => $account
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }

    public function checkoutOrder(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $request->validate([
            'order_id' => 'required',
            'serviceFee' => 'numeric',
            'discount' => 'numeric',
            'account' => 'required',
            'payment_method' => 'required',
            'user_id' => 'required',
            'warehouse_id' => 'required',
        ]);
        $journal = new Journal();
        $invoice_num = $journal->sales_journal();
        $serial = Transaction::generateSerialNumber('SO', $request->user_id);

        DB::beginTransaction();

        try {

            $order->update([
                'invoice' => $invoice_num,
                'status' => 'Completed'
            ]);

            if ($request->cart !== null) {
                foreach ($request->cart as $item) {
                    // dd($item, $request->account, $request->payment);
                    $product = Product::find($item['id']);
                    if (!$product) {
                        continue; // Skip if the product is not found
                    }

                    $jual = ($item['price'] * $item['qty']) - $request->discount;
                    $modal = $product->cost * $item['qty'];
                    // $initial_stock = $product->end_stock;
                    // $initial_cost = $product->price;
                    // $initTotal = $initial_stock * $initial_cost;

                    if ($request->payment_method == 'Credit') {
                        $this->addToReceivable($invoice_num, $request->account, $jual, 'Penjualan Barang (Code:' . $product->code . ') ' . $product->name . ' (' . -$item['qty'] . 'Pcs)', $request->user_id, date('Y-m-d H:i'), $request->dueDate, $request->contact_id);
                        $rcv = 'Receivable';
                    }

                    $this->addToJournal($invoice_num, $request->account, "40100-001", $jual, 'Penjualan Barang (Code:' . $product->code . ') ' . $product->name . ' (' . -$item['qty'] . 'Pcs)', $serial, $rcv ?? null, $request->user_id, $request->warehouse_id);

                    $this->addToJournal($invoice_num, "50100-001", "10600-001", $modal, 'Pembelian Barang (Code:' . $product->code . ') ' . $product->name . ' (' . -$item['qty'] . 'Pcs)', $serial, $rcv ?? null, $request->user_id, $request->warehouse_id);


                    $transaction = new Transaction([
                        'date_issued' => now(),
                        'invoice' => $invoice_num, // Ensure $invoice is defined
                        'product_id' => $product->id,
                        'quantity' => -$item['qty'],
                        'price' => $item['price'],
                        'cost' => $product->cost,
                        'transaction_type' => 'Sales',
                        'contact_id' => $request->contact_id ?? 1,
                        'warehouse_id' => $request->user_id,
                        'user_id' => $request->warehouse_id,
                        'serial_number' => $serial,
                    ]);

                    $transaction->save();

                    $product_log = $transaction->where('product_id', $product->id)->sum('quantity');
                    $end_Stock = $product->stock + $product_log;
                    Product::where('id', $product->id)->update([
                        'end_Stock' => $end_Stock,
                        'price' => $item['price'],
                    ]);

                    $updateWarehouseStock = WarehouseStock::where('warehouse_id', $request->warehouse_id)->where('product_id', $product->id)->first();
                    $updateCurrentStock = $transaction->where('product_id', $product->id)->where('warehouse_id', $request->warehouse_id)->sum('quantity');
                    if ($updateWarehouseStock) {
                        $updateWarehouseStock->current_stock = $updateCurrentStock;
                        $updateWarehouseStock->save();
                    } else {
                        $warehouseStock = new WarehouseStock();
                        $warehouseStock->warehouse_id = $request->warehouse_id;
                        $warehouseStock->product_id = $product->id;
                        $warehouseStock->init_stock = 0;
                        $warehouseStock->current_stock = $updateCurrentStock;
                        $warehouseStock->save();
                    }
                }
            }

            if ($request->discount > 0) {
                $this->addToJournal($invoice_num, "60111-001", "40100-001", $request->discount, 'Potongan Penjualan', $serial, null, $request->user_id, $request->warehouse_id);
            }

            if ($request->serviceFee != null) {
                $this->addToJournal($invoice_num, $request->account, "40100-002", $request->serviceFee, 'Jasa Service', $serial, null, $request->user_id, $request->warehouse_id);
            }

            DB::commit();
            return response()->json([
                'message' => 'Transaction created successfully',
                'order' => $order
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'message' => 'Transaction failed',
                'error' => 'Something went wrong, contact your administrator'
            ], 500);
        }
    }
}
