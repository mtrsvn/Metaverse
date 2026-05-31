<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'purchases';

    protected $fillable = [
        'user_id',
        'product_id',
        'product_size',
        'quantity',
        'product_name',
        'product_price',
        'approved',
    ];

    public $timestamps = false;
}
