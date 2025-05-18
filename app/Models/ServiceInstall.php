<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceInstall extends Model
{
    protected $fillable = [
        'type',
        'subtype',
        'token_id',
        'ip',
        'user_agent',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}