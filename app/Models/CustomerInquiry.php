<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerInquiry extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['photos' => 'array', 'notification_sent' => 'boolean', 'asking_price' => 'decimal:2'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class)->withTrashed();
    }
}
