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
        Schema::table('job_tickets', function (Blueprint $table) {
            // Add new fields after production_id column
            $table->string('fgrn_no', 50)->nullable()->after('production_id')->comment('Finished Goods Receipt Number - Format: JT-yy-xxxxx');
            $table->date('date')->nullable()->after('fgrn_no')->comment('Job ticket date');

            // Add indexes for better query performance
            $table->index('fgrn_no', 'idx_job_tickets_fgrn_no');
            $table->index('date', 'idx_job_tickets_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_tickets', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_job_tickets_fgrn_no');
            $table->dropIndex('idx_job_tickets_date');

            // Then drop columns
            $table->dropColumn(['fgrn_no', 'date']);
        });
    }
};
