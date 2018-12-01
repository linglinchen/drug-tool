<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUserdomain extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('users_domains', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('domain_id');
            $table->integer('group_id')->nullable();
        });

    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users_domains');
    }
}