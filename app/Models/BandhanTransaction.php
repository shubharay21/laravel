<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BandhanTransaction extends Model
{
    use HasFactory;

    protected $table = 'bandhan_transactions';

    protected $fillable = [
        'triggered_by_user_id',
        'application_id',
        'action_id',
        'lot_number',
        'record_count',
        'raw_payload_path',
        'decrypted_file_path',
        'decrypted_data',
        'status',
    ];

    public function details()
    {
        return $this->hasMany(BandhanTransactionDetail::class, 'bandhan_transaction_id');
    }
}
