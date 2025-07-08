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
        Schema::table('items', function (Blueprint $table) {
            //
            $table->string('tape_mat_pcc', 255)->nullable()->after('weight');
        });
    }
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('tape_mat_pcc');
        });
    }
};
