<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderRequest extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['stock_adjusted'=>'boolean', 'subtotal'=>'decimal:2', 'delivery_quote'=>'decimal:2']; }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
}
