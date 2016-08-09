<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('entityId');
            $table->integer('userId');
            $table->integer('taskId');
            $table->integer('modifiedBy');
            $table->timestamps();

            $table->index('entityId');
            $table->index('userId');
            $table->index('taskId');
            $table->index('modifiedBy');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('assignments');
    }
}
