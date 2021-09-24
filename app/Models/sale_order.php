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
        'prdt_name',
        'auto_billing',
        'exp_billing_days',
        'ts_no',
        'description',
        'atm_pay_no',
        'web_atm_url',
        'otp_url',
        'card_pay_url'
    ];
}
