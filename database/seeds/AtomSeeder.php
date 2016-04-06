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
            $title = str_random(10);

            DB::table('atoms')->insert([
                'atomId' => $i % 5 + 1,
                'title' => $title,
                'strippedTitle' => $title
            ]);
        }
    }
}
