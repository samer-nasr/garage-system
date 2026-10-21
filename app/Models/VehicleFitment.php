<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleFitment extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function listing(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
