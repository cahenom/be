<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceMarkupTier extends Model
{
    use HasFactory;

    protected $table = 'price_markup_tiers';

    protected $fillable = [
        'min_price',
        'markup',
    ];
}
