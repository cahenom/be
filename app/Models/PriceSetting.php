<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceSetting extends Model
{
    use HasFactory;

    protected $table = 'price_settings';

    protected $fillable = [
        'role_id',
        'min_price',
        'markup',
    ];
}
