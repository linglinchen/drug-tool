<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Atom extends Model
{    
    protected $table = 'atoms';
    protected $guarded = ['id'];

    public static function findNewestIfNotDeleted($atomId) {
        $atom = self::where('atomId', '=', $atomId)->orderBy('id', 'desc')->first();

        return $atom->deleted ? null : $atom;
    }
}
