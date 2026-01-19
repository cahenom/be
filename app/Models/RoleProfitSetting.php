<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleProfitSetting extends Model
{
    protected $fillable = [
        'role_id',
        'markup_percent',
        'markup_min',
        'markup_max',
        'is_default',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
