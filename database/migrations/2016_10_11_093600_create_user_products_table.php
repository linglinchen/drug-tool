<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\User;
use App\UserProduct;

class CreateUserProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('product_id')->default(1);
            $table->integer('group_id');
        });

        foreach(User::all() as $user) {
            DB::table('user_products')->insert([
                'user_id' => $user->id,
                'group_id' => $user->group_id
            ]);
        }

        Schema::table('users', function ($table) {
            $table->dropColumn('group_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function ($table) {
            $table->integer('group_id')->nullable();
        });

        foreach(UserProduct::all() as $userProduct) {
            $user = User::find($userProduct->user_id);
            $user->group_id = $userProduct->group_id;
            $user->save();
        }

        Schema::drop('user_products');
    }
}
