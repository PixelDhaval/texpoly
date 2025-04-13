<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'short_code',
        'category_id',
        'subcategory_id',
        'label_name',
        'grade',
        'unit',
        'price',
        'quantity',
        'weight',
    ];

    protected $with = ['category', 'subcategory'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }
}
