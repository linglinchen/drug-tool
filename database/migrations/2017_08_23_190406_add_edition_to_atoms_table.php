<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEditionToAtomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('atoms', function (Blueprint $table) {
            //
            $table->float('edition', 8, 3)->nullable()->comment('MAJOR_or_year.MINOR_or_revision');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('atoms', function (Blueprint $table) {
            $table->dropColumn('edition');
        });
    }
}
