<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
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
