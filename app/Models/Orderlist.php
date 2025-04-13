<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orderlist extends Model
{
    protected $fillable = [
        'order_id',
        'packinglist_id',
        'dispatch_qty'
    ];
    
    protected $with = ['packinglist'];

    public function packinglist()
    {
        return $this->belongsTo(Packinglist::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
