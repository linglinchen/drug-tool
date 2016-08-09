<?php

use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tasks = [
            [
                'title' => 'New word'
            ],
            [
                'title' => 'New word awaiting approval'
            ],
            [
                'title' => 'Edited word'
            ],
            [
                'title' => 'Edited word awaiting approval'
            ],
            [
                'title' => 'Awaiting copyeditor review'
            ],
            [
                'title' => 'Re-work'
            ]
        ];

        foreach($tasks as $task) {
            DB::table('tasks')->insert($task);
        }
    }
}
