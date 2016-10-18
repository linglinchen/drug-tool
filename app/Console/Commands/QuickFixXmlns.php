<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;

class QuickFixXmlns extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:xmlns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes misapplications of the xmlns attribute.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $atoms = Atom::whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })->get();

        $totalReplaced = 0;
        $totalReplacedAtoms = 0;
        $detector = '/(?<!<math) xmlns:mml="[^"]*"/';

        foreach($atoms as $atom) {
            $newTitle = preg_replace($detector, '', $atom->title, -1, $count);
            $totalReplaced += $count;

            $newXml = preg_replace($detector, '', $atom->xml, -1, $count);
            $totalReplaced += $count;

            if($newXml != $atom->xml || $newTitle != $atom->title) {
                $newAtom = $atom->replicate();
                $newAtom->title = $newTitle;
                $newAtom->xml = $newXml;
                $newAtom->modified_by = null;       //system
                $newAtom->save();
                ++$totalReplacedAtoms;
            }
        }

        echo 'Total atoms: ', count($atoms), "\n";
        echo 'Total updated atoms: ', $totalReplacedAtoms, "\n";
        echo 'Total text replacements: ', $totalReplaced, "\n";
    }
}
