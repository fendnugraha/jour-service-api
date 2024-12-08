<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Http\Resources\ChartOfAccountResource;

class ChartOfAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $chartOfAccounts = ChartOfAccount::orderBy('acc_code')->paginate(10);
        return new ChartOfAccountResource($chartOfAccounts, true, "Successfully fetched chart of accounts");
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
        $chartOfAccount = new ChartOfAccount();
        $request->validate([
            'name' => 'required|unique:chart_of_accounts,acc_name',
            'category_id' => 'required',
            'st_balance' => 'numeric',
        ]);

        $chartOfAccount->create([
            'acc_code' => $chartOfAccount->acc_code($request->category_id),
            'acc_name' => $request->name,
            'account_id' => $request->category_id,
            'st_balance' => $request->st_balance ?? 0,
        ]);

        return response()->json([
            'message' => 'Chart of account created successfully',
        ])->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ChartOfAccount $chartOfAccount)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChartOfAccount $chartOfAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChartOfAccount $chartOfAccount)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChartOfAccount $chartOfAccount)
    {
        //
    }
}
