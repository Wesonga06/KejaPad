<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
    ];

    // Human-readable label for the setting key.
    public function label(): string
    {
        return ucfirst(str_replace(['.', '_'], [' › ', ' '], $this->key));
    }
}
