<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Group;

class AddLevelToGroups extends Migration
{
    private static $levels = [
        'Internal Editor' => 1,
        'Editorial' => 2,
        'BPPM' => 3,
        'Book Production project Manager' => 3,
        'Developer' => 4,
        'Developer God' => 5
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function($table) {
            $table->integer('level')->default(0)->after('title');
            $table->index('level');
        });

        //set admin levels to sensible defaults
        foreach(self::$levels as $groupTitle => $level) {
            Group::where('title', 'like', $groupTitle)
                    ->update(['level' => $level]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('groups', function($table) {
            $table->dropColumn('level');
        });
    }
}
