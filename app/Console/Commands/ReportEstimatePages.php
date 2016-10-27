<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Molecule;

use DB;

class ReportEstimatePages extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:estimatePages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Estimates printable character counts per chapter, and caches the results for later use in other reports.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $stats = [];

        $molecules = Molecule::all();
        foreach($molecules as $molecule) {
            $code = $molecule['code'];
            $moleculeStats = [
                'code' => $code,
                'chars' => self::_countChars($code)
            ];

            $stats[] = $moleculeStats;
        }

        $cacheDir = base_path() . '/data/cache/';
        if(!is_dir($cacheDir)) {
            mkdir($cacheDir);
        }
        file_put_contents($cacheDir . 'moleculeStats.json', json_encode($stats));

        print_r($stats);
    }

    /**
     * Estimate the number of printable characters in a chapter.
     *
     * @param string $moleculeCode
     *
     * @return integer
     */
    protected static function _countChars($moleculeCode) {
        $latestIds = Atom::select()
                ->where('molecule_code', '=', $moleculeCode)
                ->whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                });

        $wordsQuery = DB::table(DB::raw('(' . $latestIds->toSql() . ') AS latestIds'))
                ->select(DB::raw("regexp_matches(regexp_replace(regexp_replace(xml, '<[^>]*>', '', 'g'), '^\\s+', ''), '\\s+', 'g')"))
                ->mergeBindings($latestIds->getQuery());

        $countQuery = DB::table(DB::raw('(' . $wordsQuery->toSql() . ') AS wordsQuery'))
                ->select(DB::raw('COUNT(*)'))
                ->mergeBindings($wordsQuery);

        return $countQuery->get()[0]->count;
    }
}
