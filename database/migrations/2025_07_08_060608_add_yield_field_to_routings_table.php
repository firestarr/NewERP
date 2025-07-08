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
        Schema::table('routings', function (Blueprint $table) {
            $table->decimal('yield', 8, 4)->nullable()->after('set_jump')->comment('Yield percentage (0-100)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routings', function (Blueprint $table) {
            $table->dropColumn('yield');
        });
    }
};
