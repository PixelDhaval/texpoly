<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Packinglist extends Model
{
    protected $fillable = [
        'customer_id',
        'product_id',
        'label_name',
        'customer_qty',
        'unit',
        'price',
        'quantity',
        'weight',
        'is_bold',
        'stock',
    ];

    protected $with = ['customer', 'product'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
