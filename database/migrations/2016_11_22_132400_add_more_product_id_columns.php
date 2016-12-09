<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMoreProductIdColumns extends Migration
{
    protected $_tablesToAlter = ['tasks', 'statuses'];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach($this->_tablesToAlter as $tableName) {
            Schema::table($tableName, function ($table) {
                $table->integer('product_id')->default(1);

                $table->index('product_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach($this->_tablesToAlter as $tableName) {
            Schema::table($tableName, function ($table) {
                $table->dropColumn('product_id');
            });
        }
    }
}
