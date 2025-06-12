<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('SalesOrder', function (Blueprint $table) {
            if (!Schema::hasColumn('SalesOrder', 'po_number_customer')) {
                $table->string('po_number_customer', 100)->nullable()->after('so_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('SalesOrder', function (Blueprint $table) {
            if (Schema::hasColumn('SalesOrder', 'po_number_customer')) {
                $table->dropColumn('po_number_customer');
            }
        });
    }
};
