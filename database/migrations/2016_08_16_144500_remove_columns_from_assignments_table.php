<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveColumnsFromAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function($table) {
            $table->dropColumn('taskStart');
            $table->dropColumn('active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function($table) {
            $table->timestamp('taskStart')->nullable();
            $table->tinyInteger('active')->default(1);

            $table->index('active');
        });
    }
}
