<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;

class QuickFix extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command performs whatever quick fixes you fill it with.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        self::fixUntypedDrugNodes();
    }

    public static function fixUntypedDrugNodes() {
        $atoms = Atom::whereIn('id', Atom::buildLatestIDQuery())->get();
        foreach($atoms as $atom) {
            $newXml = str_replace('<drug>', '<drug type="generic">', $atom->xml);
            if($newXml != $atom->xml) {
                $newAtom = $atom->replicate();
                $newAtom->xml = $newXml;
                $newAtom->modified_by = null;
                $newAtom->save();
            }
        }
    }
}
