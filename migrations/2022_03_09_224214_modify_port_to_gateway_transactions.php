<?php

use Mozakar\Gateway\Enum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyPortToGatewayTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $ports = implode("', '", (array) Enum::getIPGs());
        \DB::statement("ALTER TABLE gateway_transactions MODIFY COLUMN port ENUM('{$ports}')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
