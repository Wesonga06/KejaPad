<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'body',
    ];

    // Replace {{ placeholders }} in a template body with the given values.
    public static function renderBody(string $body, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($matches) use ($vars) {
            $key = $matches[1];

            return array_key_exists($key, $vars) ? (string) $vars[$key] : $matches[0];
        }, $body);
    }

    public function render(array $vars): string
    {
        return self::renderBody($this->body, $vars);
    }
}
