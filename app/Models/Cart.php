<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'cart';

    protected $fillable = [
        'user_id',
        'product_id',
        'product_name',
        'product_price',
        'product_image',
        'product_size',
        'quantity',
    ];

    public $timestamps = false;

    public $incrementing = false;
}
