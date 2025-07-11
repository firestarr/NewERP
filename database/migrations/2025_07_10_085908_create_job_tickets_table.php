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
        Schema::create('job_tickets', function (Blueprint $table) {
            $table->id('ticket_id');
            $table->string('item', 100); // Item name/code
            $table->string('uom', 20); // Unit of Measure
            $table->float('qty_completed'); // Quantity completed
            $table->string('ref_jo_no', 50); // Reference/JO Number
            $table->date('issue_date_jo'); // Issue Date JO
            $table->float('qty_jo'); // QTY JO
            $table->string('customer', 100); // Customer name
            $table->unsignedBigInteger('production_id'); // Reference to production order
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('production_id')->references('production_id')->on('production_orders');

            // Index for better performance
            $table->index('ref_jo_no');
            $table->index('production_id');
            $table->index('issue_date_jo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_tickets');
    }
};
