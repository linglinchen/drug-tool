<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCurrenteditionToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->float('current_edition', 8, 3)->after('product_id')->nullable()->comment('MAJOR_or_year.MINOR_or_revision');
        });

        DB::table('products')
        ->where('id', 1)
        ->update(['current_edition' => '2020']);

        DB::table('products')
        ->where('id', 4)
        ->update(['current_edition' => '2020']);

        DB::table('products')
        ->where('id', 7)
        ->update(['current_edition' => '7']);

        DB::table('products')
        ->where('id', 8)
        ->update(['current_edition' => '11']);

        DB::table('products')
        ->where('id', 10)
        ->update(['current_edition' => '8']);

        DB::table('products')
        ->where('id', 11)
        ->update(['current_edition' => '8']);

        DB::table('products')
        ->where('id', 12)
        ->update(['current_edition' => '2019']);

        DB::table('products')
        ->where('id', 13)
        ->update(['current_edition' => '8']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('current_edition');
        });
    }
}
