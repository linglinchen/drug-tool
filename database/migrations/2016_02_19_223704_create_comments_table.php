<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parentId')->default(0);
            $table->integer('atomId');
            $table->integer('userId');
            $table->text('text');
            $table->boolean('deleted')->default(false);
            $table->timestamps();

            $table->index('parentId');
            $table->index('deleted');

            $table->foreign('atomId')->references('id')->on('atoms');
            $table->foreign('userId')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('comments');
    }
}
