<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProductsTableAddColumnTermTypeCategoryType extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('termtype')->nullable()->comment('indicating various atom terminology');

        });

        Schema::table('products', function (Blueprint $table) {
            $table->text('categorytype')->nullable()->comment('indicating various category terminology');

        });

        DB::table('products')
        ->where('id', 1)
        ->update(['termtype' => 'Monograph', 'categorytype' => NULL]);

        DB::table('products')
        ->where('id', 2)
        ->update(['termtype' => 'Monograph', 'categorytype' => NULL]);

        DB::table('products')
        ->where('id', 3)
        ->update(['termtype' => 'Term', 'categorytype' => 'Domain']);

        DB::table('products')
        ->where('id', 4)
        ->update(['termtype' => 'Monograph', 'categorytype' => NULL]);

        DB::table('products')
        ->where('id', 5)
        ->update(['termtype' => 'Term', 'categorytype' => 'Category']);

        DB::table('products')
        ->where('id', 6)
        ->update(['termtype' => 'Section', 'categorytype' => 'Content Area']);

        DB::table('products')
        ->where('id', 7)
        ->update(['termtype' => 'Question', 'categorytype' => 'Content Area']);

        DB::table('products')
        ->where('id', 8)
        ->update(['termtype' => 'Term', 'categorytype' => 'Category']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('termtype');
            $table->dropColumn('categorytype');
        });
    }
}
