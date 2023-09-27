<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    protected $primaryKey = 'item_id';
    protected $table = 'inventory';
    protected $fillable = [
        'item_name',
        'uom_id',
        'item_description',
        'category_id',
        'status',
        'unit_price',
        'cogs',
        'added_by',
        'created_at',
        'updated_at'

    ];
}
