<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rfq_vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('vendor_id');
            $table->enum('status', ['selected', 'sent', 'quoted', 'rejected'])->default('selected');
            $table->timestamp('selected_at')->useCurrent();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            $table->foreign('rfq_id')->references('rfq_id')->on('request_for_quotations')->onDelete('cascade');
            $table->foreign('vendor_id')->references('vendor_id')->on('vendors')->onDelete('cascade');
            
            // Prevent duplicate vendor selection for same RFQ
            $table->unique(['rfq_id', 'vendor_id']);
            
            $table->index(['rfq_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rfq_vendors');
    }
};