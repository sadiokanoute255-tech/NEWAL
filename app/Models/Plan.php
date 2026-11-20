<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['name', 'max_guests', 'price', 'currency', 'billing_period', 'is_active', 'sort_order'];
}
