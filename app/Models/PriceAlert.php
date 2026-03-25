<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceAlert extends Model
{
    protected $fillable = ['product', 'state', 'message', 'spike_pct', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'spike_pct' => 'float'];
    }
}
