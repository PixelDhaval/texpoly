<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'order_date',
        'order_no',
        'status',
        'container_no',
        'sgs_seal_no',
        'line_seal_no',
        'target_date',
        'note'
    ];

    protected $with = ['customer'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }   

    public function orderlists()
    {
        return $this->hasMany(Orderlist::class);
    }
}
