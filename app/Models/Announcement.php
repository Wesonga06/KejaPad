<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'property_id',
        'title',
        'body',
        'category',
        'audience',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function categoryColor()
    {
        return match ($this->category) {
            'water' => 'info',
            'electricity' => 'warning',
            'security' => 'danger',
            'maintenance' => 'secondary',
            'other' => 'dark',
            default => 'primary',
        };
    }

    public function categoryIcon()
    {
        return match ($this->category) {
            'water' => 'bi-droplet',
            'electricity' => 'bi-lightning',
            'security' => 'bi-shield-exclamation',
            'maintenance' => 'bi-tools',
            'other' => 'bi-three-dots',
            default => 'bi-megaphone',
        };
    }
}
