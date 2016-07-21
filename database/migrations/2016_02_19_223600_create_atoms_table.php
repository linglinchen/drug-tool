<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAtomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('atoms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('entityId');
            $table->string('moleculeId')->nullable();
            $table->string('title');
            $table->string('alphaTitle');
            $table->text('xml');
            $table->timestamps();

            $table->index('entityId');
            $table->index('moleculeId');

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('atoms');
    }
}
