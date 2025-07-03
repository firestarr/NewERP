<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotesToRequestForQuotationsTable extends Migration
{
    public function up()
    {
        Schema::table('request_for_quotations', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('status');
            $table->string('reference_document')->nullable()->after('notes');
        });
    }

    public function down()
    {
        Schema::table('request_for_quotations', function (Blueprint $table) {
            $table->dropColumn(['notes', 'reference_document']);
        });
    }
}
