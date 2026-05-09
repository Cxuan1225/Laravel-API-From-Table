<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Customer extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'credit_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
