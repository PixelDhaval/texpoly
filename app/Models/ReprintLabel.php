<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReprintLabel extends Model
{
    protected $fillable = [
        'bale_no',
        'packing_list_id',
        'user_id',
        'qc',
        'finalist',
        'ref_bale_id',
        'is_approved',
        'is_printed',
    ];

    protected $with = ['packingList', 'user', 'qcEmployee', 'finalistEmployee', 'refBale'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];

    public function packingList()
    {
        return $this->belongsTo(PackingList::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function qcEmployee()
    {
        return $this->belongsTo(Employee::class, 'qc');
    }

    public function finalistEmployee()
    {
        return $this->belongsTo(Employee::class, 'finalist');
    }

    public function refBale()
    {
        return $this->belongsTo(Bale::class, 'ref_bale_id');
    }
}