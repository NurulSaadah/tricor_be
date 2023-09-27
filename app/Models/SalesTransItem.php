<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTransItem extends Model
{
    use HasFactory;
    protected $primaryKey = 'sales_item_id';
    protected $table = 'sales_trans_item';
    protected $fillable = [
        'sales_id',
        'item_id',
        'unit_price',
        'quantity',
        'total_excl_tax',
        'created_at',
        'updated_at'
    ];
}
