<?php

use Illuminate\Database\Seeder;

class AtomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i = 0; $i < 100; ++$i) {
            DB::table('atoms')->insert([
                'atomId' => $i % 5 + 1,
            ]);
        }
    }
}
