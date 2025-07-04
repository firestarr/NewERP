<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('routings', function (Blueprint $table) {
            $table->integer('cavity')->nullable();
            $table->string('process')->nullable();
            $table->decimal('set_jump')->nullable();
        });
    }

    public function down()
    {
        Schema::table('routings', function (Blueprint $table) {
            $table->dropColumn('cavity');
            $table->dropColumn('process');
            $table->dropColumn('set_jump');
        });
    }
};
