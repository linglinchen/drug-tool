<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

use App\Atom;

class Assignment extends Model
{
	protected $table = 'assignments';
	protected $guarded = ['id'];
	protected $dates = ['created_at', 'updated_at'];

	public static function getList($filters) {
		$output = self::orderBy('taskId');

		if($filters) {
			if(isset($filters['userId'])) {
				$output = $output->where('userId', '=', $filters['userId']);
			}
		}
		$output = $output->get()
				->toArray();

		$entityIds = array_column($output, 'atomEntityId');
		$atoms = Atom::findNewest($entityIds)
				->get()
				->toArray();
		foreach($output as &$row) {
			foreach($atoms as $atomKey => $atom) {
				if($atom['entityId'] == $row['atomEntityId']) {
					$row['atom'] = $atom;
					unset($atoms[$atomKey]);
				}
			}
		}

		return $output;
	}
}
