<?php

use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $statuses = [
            [
                'id' => 0,
                'title' => 'Deactivated'
            ],
            [
                'id' => 100,
                'title' => 'Development'
            ],
            [
                'id' => 200,
                'title' => 'Gold'
            ]
        ];

        foreach($statuses as $status) {
            DB::table('statuses')->insert($status);
        }
    }
}
