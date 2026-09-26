<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class StripHtml implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?string
    {
        return $value !== null ? strip_tags($value) : null;
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        return $value !== null ? strip_tags($value) : null;
    }
}
