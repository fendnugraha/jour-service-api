<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public static function getOrderNumber()
    {
        // Ambil nilai MAX(RIGHT(invoice, 7)) untuk user saat ini dan hari ini
        $lastInvoice = DB::table('orders')
            ->where('user_id', auth()->user()->id)
            ->whereDate('created_at', today())
            ->max(DB::raw('RIGHT(order_number, 7)')); // Gunakan max langsung

        // Tentukan nomor urut invoice
        $kd = $lastInvoice ? (int)$lastInvoice + 1 : 1; // Jika ada, tambahkan 1, jika tidak mulai dari 1

        // Kembalikan format invoice
        return 'ORDER#' . now()->format('dmY')  . auth()->user()->id  . str_pad($kd, 7, '0', STR_PAD_LEFT);
    }
}
