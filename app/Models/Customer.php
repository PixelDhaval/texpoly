<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'country',
        'label_id',
        'short_code',
        'is_active',
        'is_qr',
        'is_bale_no',
        'is_printed_by',
    ];

    protected $with = ['label'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_qr' => 'boolean',
        'is_bale_no' => 'boolean',
        'is_printed_by' => 'boolean',
    ];

    public function label()
    {
        return $this->belongsTo(Label::class);
    }

    public function packinglists()
    {
        return $this->hasMany(Packinglist::class);
    }
}
