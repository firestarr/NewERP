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
        Schema::table('SOLine', function (Blueprint $table) {
            // Add delivery_date column after uom_id
            $table->date('delivery_date')->nullable()->after('uom_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('SOLine', function (Blueprint $table) {
            $table->dropColumn('delivery_date');
        });
    }
};
