<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSaleOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            // [`顧客名稱`, `是否自動請款`, `自動請款天數`, `交易編號`, `回應訊息`, `虛擬帳號`, `Web ATM 網址`, `一次性密碼網址`, `信用卡付款網址`]
            $table->string('prdt_name', 50)->comment('顧客名稱');
            $table->string('auto_billing', 1)->comment('是否自動請款');
            $table->tinyInteger('exp_billing_days')->comment('自動請款天數');
            $table->string('ts_no', 20)->comment('交易編號');
            $table->string('description', 1)->comment('回應訊息');
            $table->string('atm_pay_no', 50)->comment('虛擬帳號');
            $table->string('web_atm_url', 14)->comment('Web ATM 網址');
            $table->string('otp_url', 255)->comment('一次性密碼網址');
            $table->string('card_pay_url', 255)->comment('信用卡付款網址');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropColumn(['prdt_name', 'auto_billing', 'exp_billing_days', 'ts_no', 'description', 'atm_pay_no', 'web_atm_url', 'otp_url', 'card_pay_url']);
        });
    }
}
