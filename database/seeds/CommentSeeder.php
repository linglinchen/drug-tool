<?php

use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i = 0; $i < 100; ++$i) {
            DB::table('comments')->insert([
                'atomId' => $i % 5 + 1,
                'parentId' => 0,
                'userId' => mt_rand(1, 99),
                'text' => str_random(24),
            ]);
        }
    }
}
