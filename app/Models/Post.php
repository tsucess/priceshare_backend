<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    protected $fillable = [
        'user_id', 'product', 'price', 'category', 'state',
        'market', 'location', 'lat', 'lng', 'gps_accuracy',
        'description', 'image_url', 'is_flagged',
    ];

    protected function casts(): array
    {
        return ['price' => 'float', 'is_flagged' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PostVote::class);
    }

    public function confirms(): HasMany
    {
        return $this->hasMany(PostVote::class)->where('type', 'confirm');
    }

    public function denies(): HasMany
    {
        return $this->hasMany(PostVote::class)->where('type', 'deny');
    }
}
