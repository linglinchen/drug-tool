<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use \App\Molecule;

class MoleculeSort extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('molecules', function($table) {
            $table->integer('sort')->nullable();
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
        Schema::table('molecules', function($table) {
            $table->dropColumn('sort');
        });
    }
}
