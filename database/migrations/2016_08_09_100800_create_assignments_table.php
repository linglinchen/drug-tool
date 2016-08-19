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
            $table->string('atomEntityId');
            $table->integer('userId');
            $table->integer('taskId');
            $table->integer('createdBy')->nullable();
            $table->timestamp('taskStart')->nullable();
            $table->timestamp('taskEnd')->nullable();
            $table->tinyInteger('active')->default(1);
            $table->timestamps();

            $table->index('atomEntityId');
            $table->index('userId');
            $table->index('taskId');
            $table->index('createdBy');
            $table->index('active');
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
