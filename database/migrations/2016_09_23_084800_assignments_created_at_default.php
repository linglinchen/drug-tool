<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use \App\Assignment;

class AssignmentsCreatedAtDefault extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement('ALTER TABLE assignments ALTER COLUMN created_at SET DEFAULT now();');
        \DB::transaction(function () {
            $assignments = Assignment::select()
                    ->whereNull('created_at')
                    ->get();
            foreach($assignments as $assignment) {
                $assignment->created_at = $assignment->freshTimestampString();
                $assignment->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement('ALTER TABLE assignments ALTER COLUMN created_at SET DEFAULT NULL;');
    }
}
