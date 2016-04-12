<?php

use Illuminate\Database\Seeder;

use App\Atom;

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
                'entityId' => Atom::makeUID(),
                'title' => $title,
                'alphaTitle' => $title
            ]);
        }
    }
}
