<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Http\Resources\ChartOfAccountResource;
use Illuminate\Support\Facades\Validator as FacadesValidator;

class ChartOfAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $chartOfAccounts = ChartOfAccount::with(['account', 'warehouse'])->orderBy('acc_code')->paginate(10);
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
        $validateData = FacadesValidator::make($request->all(), [
            'category_id' => 'required',  // Make sure category_id is present
            'name' => ['required', 'string', 'max:255', 'unique:chart_of_accounts,acc_name'],  // Correct unique validation rule
            'st_balance' => ['nullable', 'numeric'],  // Allow st_balance to be nullable
        ]);

        if ($validateData->fails()) {
            return response()->json([
                'message' => $validateData->errors(),  // Return detailed error messages
            ])->setStatusCode(422);
        }

        try {
            $chartOfAccount->create([
                'acc_code' => $chartOfAccount->acc_code($request->category_id),
                'acc_name' => $request->name,
                'account_id' => $request->category_id,
                'st_balance' => $request->st_balance ?? 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Chart of account already exists',
            ])->setStatusCode(409);
        }

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
    public function destroy($id)
    {
        $chartOfAccount = ChartOfAccount::find($id);

        if (!$chartOfAccount) {
            return response()->json([
                'message' => 'Chart of account not found.',
            ], 404); // Return a 404 error if not found
        }

        try {
            // Deleting the Chart of Account
            $chartOfAccount->delete();

            // Return a success response
            return response()->json([
                'message' => 'Chart of account deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete chart of account. ' . $e->getMessage(),
            ], 500);
        }
    }
}
