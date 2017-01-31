<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDomainToAccessControlStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //insert manage_domain to access_control_structure table
        DB::table('access_control_structure')->insert([
            'access_key' => 'manage domains',
            'title' => 'Manage Domains'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
