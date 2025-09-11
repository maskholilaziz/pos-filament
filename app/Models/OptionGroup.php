<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OptionGroup extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_option');
    }
}
