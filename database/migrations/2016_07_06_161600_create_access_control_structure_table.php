<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccessControlStructureTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('access_control_structure', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parentId')->nullable();
            $table->string('accessKey')->unique();
            $table->string('title');
            $table->timestamps();

            $table->index('parentId');
            $table->index('accessKey');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('access_control_structure');
    }
}
