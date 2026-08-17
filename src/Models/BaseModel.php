<?php

namespace SVR\LaravelCore\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use SVR\LaravelCore\Models\Traits\HasPublicId;

abstract class BaseModel extends Model
{
    use HasPublicId;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}