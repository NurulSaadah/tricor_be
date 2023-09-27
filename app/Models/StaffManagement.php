<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffManagement extends Model
{
    use HasFactory;
    protected $primaryKey = 'user_id';
    protected $table = 'staff_management';
    protected $fillable = [
        'name',
        'reporting_manager_id',
        'nric_no',
        'contact_no',
        'added_by',
        'created_at',
        'updated_at'

    ];
}
