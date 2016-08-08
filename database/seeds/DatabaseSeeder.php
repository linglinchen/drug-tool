<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	Model::unguard();

        DB::transaction(function() {
            $this->call(UserSeeder::class);
            $this->call(AtomSeeder::class);
            $this->call(CommentSeeder::class);
            $this->call(GroupSeeder::class);
            $this->call(StatusSeeder::class);
        });

        Model::reguard();
    }
}
