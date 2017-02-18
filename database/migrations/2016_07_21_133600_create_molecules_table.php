<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMoleculesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('molecules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('title');
            $table->timestamps();
            $table->tinyInteger('locked')->default(0);
            $table->integer('sort')->nullable();

            $table->index('code');

            $table->softDeletes();
        });

         $i = 0;
        $molecules = Molecule::select()->orderBy('id', 'ASC')->get();
        foreach($molecules as $molecule) {
            $molecule->sort = ++$i;
            $molecule->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('molecules');
    }
}
