<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTransaction extends Model
{
    use HasFactory;
    protected $primaryKey = 'sales_id';
    protected $table = 'sales_transaction';
    protected $fillable = [
        'reference_no',
        'client_name',
        'client_address',
        'client_contact_no',
        'total_excl_tax',
        'total_tax',
        'total_incl_tax',
        'total_cogs',
        'discount',
        'isPostGL',
        'isCancel',
        'notes',
        'remark',
        'created_by',
        'created_at',
        'updated_at'
    ];

    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        // Listen for the "creating" event and generate the order number
        static::creating(function ( $createTransaction) {
            $createTransaction->reference_no = 'CS-' . date('YmdHis') . '-' . str_pad( $createTransaction->created_by, 1, '0', STR_PAD_LEFT);
        });
    }
}
