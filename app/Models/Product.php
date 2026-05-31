<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'title',
        'price',
        'category',
        'description',
        'image',
        'display_order',
        'discount',
        'stock',
        'size_stock',
        'is_preorder',
        'preorder_note',
    ];

    public $timestamps = false;
}
