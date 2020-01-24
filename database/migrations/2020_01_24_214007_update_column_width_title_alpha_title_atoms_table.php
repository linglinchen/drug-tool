<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateColumnWidthTitleAlphaTitleAtomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('atoms', function (Blueprint $table) {
            $table->string('title', 1025)->change();
            $table->string('alpha_title', 1025)->change();
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
            $table->string('title', 255)->change();
            $table->string('alpha_title', 255)->change();
        });
    }
}
