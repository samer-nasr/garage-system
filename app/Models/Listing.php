<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    public function fitments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VehicleFitment::class);
    }

    public function section(): string
    {
        return ['part'=>'parts', 'car'=>'rentals', 'service'=>'services', 'dismantling'=>'dismantling', 'sale'=>'cars-for-sale'][$this->type];
    }

    public static function label(?string $value): string
    {
        $key = 'catalog.'.\Illuminate\Support\Str::slug($value ?? '');
        return \Illuminate\Support\Facades\Lang::has($key) ? __($key) : ($value ?? '');
    }

    public function contactMessage(): string
    {
        return __('business.message_'.$this->type, ['title'=>$this->translated('title'), 'reference'=>$this->part_number ?: '#'.$this->id]).' '.route('listing', [app()->getLocale(), $this]);
    }
    protected $guarded = ['id'];
    protected function casts(): array
    {
        return ['title'=>'array', 'description'=>'array', 'images'=>'array', 'price'=>'decimal:2', 'is_active'=>'boolean', 'is_demo'=>'boolean'];
    }
    public function translated(string $field): string
    {
        return ($this->{$field}[app()->getLocale()] ?? '') ?: ($this->{$field}['lt'] ?? '');
    }
    public function getImageUrlAttribute(): ?string
    {
        return isset($this->images[0]) ? asset('storage/'.$this->images[0]) : null;
    }
}
