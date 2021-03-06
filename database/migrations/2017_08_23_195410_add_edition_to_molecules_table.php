<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEditionToMoleculesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('molecules', function (Blueprint $table) {
            $table->float('edition', 8, 3)->after('product_id')->nullable()->comment('MAJOR_or_year.MINOR_or_revision');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('molecules', function (Blueprint $table) {
            $table->dropColumn('edition');
        });
    }
}
