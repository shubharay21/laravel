<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BandhanTransactionDetail extends Model
{
    use HasFactory;

    protected $table = 'bandhan_transaction_details';

    protected $fillable = [
        'bandhan_transaction_id',
        'lot_number',
        'transaction_id',
        'beneficiary_name',
        'column_3',
        'account_number',
        'beneficiary_id',
        'raw_row',
    ];

    public function transaction()
    {
        return $this->belongsTo(BandhanTransaction::class, 'bandhan_transaction_id');
    }
}
