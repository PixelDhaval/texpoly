<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bale extends Model
{
    protected $fillable = [
        'bale_no',
        'packinglist_id',
        'qc',
        'finalist',
        'type',
        'plant_id',
        'ref_bale_id',
        'ref_packinglist_id',
        'created_at',
    ];

    protected $with = ['packinglist', 'plant', 'refBale', 'refPackinglist', 'qcEmployee', 'finalistEmployee'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];

    public function packinglist()
    {
        return $this->belongsTo(Packinglist::class);
    }

    public function qcEmployee()
    {
        return $this->belongsTo(Employee::class, 'qc');
    }

    public function finalistEmployee()
    {
        return $this->belongsTo(Employee::class, 'finalist');
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function refBale()
    {
        return $this->belongsTo(Bale::class, 'ref_bale_id');
    }

    public function refPackinglist()
    {
        return $this->belongsTo(Packinglist::class, 'ref_packinglist_id');
    }
}
