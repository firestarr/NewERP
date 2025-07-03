<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('routing_operations', function (Blueprint $table) {
            $table->string('work_flow', 100)->nullable()->after('operation_name');
            $table->string('models', 100)->nullable()->after('work_flow');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('routing_operations', function (Blueprint $table) {
            $table->dropColumn(['work_flow', 'models']);
        });
    }
};
