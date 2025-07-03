<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReservationReferenceToDeliveryLine extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('DeliveryLine', function (Blueprint $table) {
            $table->string('reservation_reference')->nullable()->after('batch_number')->comment('Reference for reserved stock');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('DeliveryLine', function (Blueprint $table) {
            $table->dropColumn('reservation_reference');
        });
    }
}
