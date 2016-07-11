<?php

use Illuminate\Database\Seeder;

use \App\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'groupId' => '1',
            'username' => 'test',
            'firstname' => 'Testy',
            'lastname' => 'Testerson',
            'email' => 'test@domain.com',
            'password' => Hash::make('test'),
            'role' => 'user'
        ]);
    }
}
