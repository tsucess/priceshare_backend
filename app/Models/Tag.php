<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name', 'color', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tags');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
