<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFlagToTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('flag')->nullable()->comment('indicating what kind of task it is');

        });
        DB::table('tasks')
        ->where('id', 26)
        ->update(['flag' => 'For Editor, main term domain']);

         DB::table('tasks')
        ->where('id', 31)
        ->update(['flag' => 'For Editor, main term domain']);

        DB::table('tasks')
        ->where('id', 30)
        ->update(['flag' => 'For Editor']);

        DB::table('tasks')
        ->where('id', 29)
        ->update(['flag' => 'multiple']);

        DB::table('tasks')
        ->where('id', 556)
        ->update(['flag' => 'multiple']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('flag');
        });
    }
}
