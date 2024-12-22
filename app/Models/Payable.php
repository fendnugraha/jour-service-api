<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    protected $guarded = ['id'];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function journals()
    {
        return $this->hasMany(Journal::class, 'invoice', 'invoice');
    }

    public static function getLastPayment($invoice)
    {
        return self::where('invoice', $invoice)->max('payment_nth');
    }
}
