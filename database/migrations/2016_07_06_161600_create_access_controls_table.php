<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccessControlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('access_controls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('userId')->nullable();
            $table->integer('groupId')->nullable();
            $table->integer('accessControlStructureId');
            $table->tinyInteger('permitted');
            $table->timestamps();

            $table->index('userId');
            $table->index('groupId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('access_controls');
    }
}
