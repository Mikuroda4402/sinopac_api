<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable(false);
            $table->string('order_no', 20)->nullable(false)->comment('訂單編號');
            $table->integer('total')->nullable(false)->comment('訂單總金額');
            $table->string('pay_type', 5)->nullable(false)->comment('付款方式');
            $table->dateTime('pay_datetime')->nullable(false)->comment('付款時間');
            $table->string('status', 10)->comment('訂單狀態');
            $table->date('expire_date')->comment('訂單過期日期');
            $table->string('mailing_address', 200)->nullable(false)->comment('寄送地址');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sale_orders');
    }
}
