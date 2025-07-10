<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackingListLineTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('PackingListLine', function (Blueprint $table) {
            $table->id('line_id');
            $table->unsignedBigInteger('packing_list_id');
            $table->unsignedBigInteger('delivery_line_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('packed_quantity', 10, 4)->default(0);
            $table->unsignedBigInteger('warehouse_id');
            $table->string('batch_number', 50)->nullable();
            $table->integer('package_number')->default(1);
            $table->string('package_type', 50)->default('Box');
            $table->decimal('weight_per_unit', 10, 3)->default(0);
            $table->decimal('volume_per_unit', 10, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Foreign key constraints
            $table->foreign('packing_list_id')->references('packing_list_id')->on('PackingList')->onDelete('cascade');
            $table->foreign('delivery_line_id')->references('line_id')->on('DeliveryLine')->onDelete('cascade');
            $table->foreign('item_id')->references('item_id')->on('items')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses')->onDelete('cascade');

            // Indexes
            $table->index(['packing_list_id']);
            $table->index(['delivery_line_id']);
            $table->index(['item_id']);
            $table->index(['warehouse_id']);
            $table->index(['package_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('PackingListLine');
    }
}
