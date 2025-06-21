<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionLabour extends Model
{
    protected $fillable = [
        'subcategory_id',
        'labour_count',
        'date',
    ];

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }
}
