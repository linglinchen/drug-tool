<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDomainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('title');
            $table->timestamps();
            $table->tinyInteger('locked')->default(0);
            $table->integer('sort')->nullable();
            $table->integer('product_id')->default(3);
            $table->integer('contributor_id')->default(2);
            $table->index('code');

            $table->softDeletes();
        });

         $i = 0;
        $domains = Domain::select()->orderBy('id', 'ASC')->get();
        foreach($domains as $domain) {
            $domain->sort = ++$i;
            $domain->save();
        }
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('domains');
    }
}

