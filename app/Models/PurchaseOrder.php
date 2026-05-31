<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'user_id',
        'items',
        'total_amount',
        'status',
        'approved_at',
        'rejected_at',
        'approved_by',
        'created_at',
    ];

    public $timestamps = false;
}
