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
                'title' => 'New word'
            ],
            [
                'id' => 100,
                'title' => 'Awaiting copyeditor'
            ],
            [
                'id' => 200,
                'title' => 'Rejected by copyeditor'
            ],
            [
                'id' => 300,
                'title' => 'Approved by copyeditor'
            ],
            [
                'id' => 400,
                'title' => 'Awaiting SME'
            ],
            [
                'id' => 500,
                'title' => 'Ready to publish'
            ],
            [
                'id' => 600,
                'title' => 'Deactivated'
            ]
        ];

        foreach($statuses as $status) {
            DB::table('statuses')->insert($status);
        }
    }
}
