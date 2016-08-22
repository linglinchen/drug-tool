<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ParentIdNullableOnCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('comments', function ($table) {
            $table->dropColumn('parentId');
        });

        Schema::table('comments', function ($table) {
            $table->integer('parentId')
                    ->nullable()
                    ->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('comments', function ($table) {
            $table->dropColumn('parentId');
        });

        Schema::table('comments', function ($table) {
            $table->integer('parentId')
                    ->nullable(false)
                    ->default(0);
        });
    }
}
