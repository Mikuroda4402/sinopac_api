<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sale_order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'order_no',
        'total',
        'pay_type',
        'pay_datetime',
        'status',
        'expire_date',
        'mailing_address',
    ];
}
