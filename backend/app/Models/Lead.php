<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'name', 'company_name', 'email', 'phone', 'employee_count',
        'priority_module', 'message', 'source', 'status', 'ip_address',
    ];
}
