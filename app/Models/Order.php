<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'department_name',
        'order_no',
        'order_date',
        'approval_date',
        'po_no',
        'po_date',
    ];
}
