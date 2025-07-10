<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackingListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('PackingList', function (Blueprint $table) {
            $table->id('packing_list_id');
            $table->string('packing_list_number', 50)->unique();
            $table->date('packing_date');
            $table->unsignedBigInteger('delivery_id');
            $table->unsignedBigInteger('customer_id');
            $table->enum('status', ['Draft', 'In Progress', 'Completed', 'Shipped'])->default('Draft');
            $table->string('packed_by', 100)->nullable();
            $table->string('checked_by', 100)->nullable();
            $table->decimal('total_weight', 10, 3)->default(0);
            $table->decimal('total_volume', 10, 3)->default(0);
            $table->integer('number_of_packages')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Foreign key constraints
            $table->foreign('delivery_id')->references('delivery_id')->on('Delivery')->onDelete('cascade');
            $table->foreign('customer_id')->references('customer_id')->on('Customer')->onDelete('cascade');

            // Indexes
            $table->index(['delivery_id']);
            $table->index(['customer_id']);
            $table->index(['status']);
            $table->index(['packing_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('PackingList');
    }
}