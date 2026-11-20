<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false; // created_at est rempli par la base

    protected $fillable = [
        'user_id', 'organization_id', 'action',
        'subject_type', 'subject_id', 'meta', 'ip',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }
}
