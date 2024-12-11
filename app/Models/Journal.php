<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function scopeFilterJournals($query, array $filters)
    {
        $query->when(!empty($filters['search']), function ($query) use ($filters) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('invoice', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('cred_code', 'like', '%' . $search . '%')
                    ->orWhere('debt_code', 'like', '%' . $search . '%')
                    ->orWhere('date_issued', 'like', '%' . $search . '%')
                    ->orWhere('trx_type', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('amount', 'like', '%' . $search . '%')
                    ->orWhere('fee_amount', 'like', '%' . $search . '%')
                    ->orWhereHas('debt', function ($query) use ($search) {
                        $query->where('acc_name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('cred', function ($query) use ($search) {
                        $query->where('acc_name', 'like', '%' . $search . '%');
                    });
            });
        });
    }

    public function scopeFilterAccounts($query, array $filters)
    {
        $query->when(!empty($filters['account']), function ($query) use ($filters) {
            $account = $filters['account'];
            $query->where('cred_code', $account)->orWhere('debt_code', $account);
        });
    }

    public function scopeFilterMutation($query, array $filters)
    {
        $query->when($filters['searchHistory'] ?? false, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->whereHas('debt', function ($q) use ($search) {
                    $q->where('acc_name', 'like', '%' . $search . '%');
                })
                    ->orWhereHas('cred', function ($q) use ($search) {
                        $q->where('acc_name', 'like', '%' . $search . '%');
                    });
            });
        });
    }

    public function debt()
    {
        return $this->belongsTo(ChartOfAccount::class, 'debt_code', 'acc_code');
    }

    public function cred()
    {
        return $this->belongsTo(ChartOfAccount::class, 'cred_code', 'acc_code');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'invoice', 'invoice');
    }

    public static function invoice_journal()
    {
        // Ambil nilai MAX(RIGHT(invoice, 7)) untuk user saat ini dan hari ini
        $lastInvoice = DB::table('journals')
            ->where('user_id', auth()->user()->id)
            ->where('trx_type', '!=', 'Sales')
            ->where('trx_type', '!=', 'Purchase')
            ->whereDate('created_at', today())
            ->max(DB::raw('RIGHT(invoice, 7)')); // Gunakan max langsung

        // Tentukan nomor urut invoice
        $kd = $lastInvoice ? (int)$lastInvoice + 1 : 1; // Jika ada, tambahkan 1, jika tidak mulai dari 1

        // Kembalikan format invoice
        return 'JR.BK.' . now()->format('dmY') . '.' . auth()->user()->id . '.' . str_pad($kd, 7, '0', STR_PAD_LEFT);
    }

    public static function generate_invoice_journal($prefix, $table, $condition = [])
    {
        // Ambil nilai MAX(RIGHT(invoice, 7)) berdasarkan kondisi user dan tanggal
        $lastInvoice = DB::table($table)
            ->where('user_id', auth()->user()->id)
            ->whereDate('created_at', today())
            ->where($condition)
            ->max(DB::raw('RIGHT(invoice, 7)')); // Ambil nomor invoice terakhir (7 digit)

        // Tentukan nomor urut invoice
        $kd = $lastInvoice ? (int)$lastInvoice + 1 : 1; // Jika ada invoice, tambahkan 1, jika tidak mulai dari 1

        // Kembalikan format invoice
        return $prefix . '.' . now()->format('dmY') . '.' . auth()->user()->id . '.' . str_pad($kd, 7, '0', STR_PAD_LEFT);
    }

    public function sales_journal()
    {
        return $this->generate_invoice_journal('SO.BK', 'transactions', [['transaction_type', '=', 'Sales']]);
    }

    public function purchase_journal()
    {
        // Untuk purchase journal, kita menambahkan kondisi agar hanya mengembalikan yang quantity > 0
        return $this->generate_invoice_journal('PO.BK', 'transactions', [['quantity', '>', 0], ['transaction_type', '=', 'Purchase']]);
    }

    public static function payable_invoice($contact_id)
    {
        return self::generate_invoice_journal('PY.BK.' . $contact_id, 'payables', [['contact_id', '=', $contact_id], ['payment_nth', '=', 0]]);
    }

    public static function receivable_invoice($contact_id)
    {
        return self::generate_invoice_journal('RC.BK.' . $contact_id, 'receivables', [['contact_id', '=', $contact_id], ['payment_nth', '=', 0]]);
    }

    public static function endBalanceBetweenDate($account_code, $start_date, $end_date)
    {
        $initBalance = ChartOfAccount::where('acc_code', $account_code)->first();

        $transactions = self::where(function ($query) use ($account_code) {
            $query
                ->where('debt_code', $account_code)
                ->orWhere('cred_code', $account_code);
        })
            ->whereBetween('date_issued', [
                $start_date,
                $end_date,
            ])
            ->get();

        $debit = $transactions->where('debt_code', $account_code)->sum('amount');
        $credit = $transactions->where('cred_code', $account_code)->sum('amount');

        if ($initBalance->Account->status == "D") {
            return $initBalance->st_balance + $debit - $credit;
        } else {
            return $initBalance->st_balance + $credit - $debit;
        }
    }

    public static function equityCount($end_date, $includeEquity = true)
    {
        $coa = ChartOfAccount::all();

        foreach ($coa as $coaItem) {
            $coaItem->balance = self::endBalanceBetweenDate($coaItem->acc_code, '0000-00-00', $end_date);
        }

        $initBalance = $coa->where('acc_code', '30100-001')->first()->st_balance;
        $assets = $coa->whereIn('account_id', \range(1, 18))->sum('balance');
        $liabilities = $coa->whereIn('account_id', \range(19, 25))->sum('balance');
        $equity = $coa->where('account_id', 26)->sum('balance');

        // Use Eloquent to update a specific record
        ChartOfAccount::where('acc_code', '30100-001')->update(['st_balance' => $initBalance + $assets - $liabilities - $equity]);

        // Return the calculated equity
        return ($includeEquity ? $initBalance : 0) + $assets - $liabilities - ($includeEquity ? $equity : 0);
    }

    public function profitLossCount($start_date, $end_date)
    {
        // Use relationships if available
        $start_date = Carbon::parse($start_date)->copy()->startOfDay();
        $end_date = Carbon::parse($end_date)->copy()->endOfDay();

        $coa = ChartOfAccount::with('account')->whereIn('account_id', \range(27, 45))->get();

        $transactions = $this->selectRaw('debt_code, cred_code, SUM(amount) as total')
            ->whereBetween('date_issued', [$start_date, $end_date])
            ->groupBy('debt_code', 'cred_code')
            ->get();

        foreach ($coa as $value) {
            $debit = $transactions->where('debt_code', $value->acc_code)->sum('total');
            $credit = $transactions->where('cred_code', $value->acc_code)->sum('total');

            $value->balance = ($value->account->status == "D") ? ($value->st_balance + $debit - $credit) : ($value->st_balance + $credit - $debit);
        }

        // Use collections for filtering
        $revenue = $coa->whereIn('account_id', \range(27, 30))->sum('balance');
        $cost = $coa->whereIn('account_id', \range(31, 32))->sum('balance');
        $expense = $coa->whereIn('account_id', \range(33, 45))->sum('balance');

        // Use Eloquent to update a specific record if it exists
        $specificRecord = ChartOfAccount::where('acc_code', '30100-002')->first();
        if ($specificRecord) {
            $specificRecord->update(['st_balance' => $revenue - $cost - $expense]);
        }

        // Return the calculated profit or loss
        return $revenue - $cost - $expense;
    }

    public function cashflowCount($start_date, $end_date)
    {
        $cashAccount = ChartOfAccount::all();

        $transactions = $this->selectRaw('debt_code, cred_code, SUM(amount) as total')
            ->whereBetween('date_issued', [$start_date, $end_date])
            ->groupBy('debt_code', 'cred_code')
            ->get();

        foreach ($cashAccount as $value) {
            $debit = $transactions->where('debt_code', $value->acc_code)->sum('total');

            $credit = $transactions->where('cred_code', $value->acc_code)->sum('total');

            $value->balance = $debit - $credit;
        }

        $result = $cashAccount->whereIn('account_id', [1, 2])->sum('balance');

        return $result;
    }

    public function payable()
    {
        return $this->belongsTo(Payable::class, 'invoice', 'invoice');
    }

    public function receivable()
    {
        return $this->belongsTo(Receivable::class, 'invoice', 'invoice');
    }
}
