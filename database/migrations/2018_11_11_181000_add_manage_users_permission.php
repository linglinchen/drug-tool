<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddManageUsersPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('access_control_structure')->insert([
            'access_key' => 'manage_users',
            'title' => 'Manage users'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('access_control_structure')->where('access_key', '=', 'manage_users')->delete();
    }
}
