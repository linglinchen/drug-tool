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

	/**
	 * GET a list of all assignments or POST filters to retrieve a filtered list.
	 * Adds the appropriate atoms.
	 *
	 * @api
	 *
	 * @param Request ?array $filters The filters as key => value pairs
	 *
	 * @return array The
	 */
	public static function getList($filters) {
		$output = self::orderBy('taskId');

		if($filters) {
			if(isset($filters['userId'])) {
				$output = $output->where('userId', '=', $filters['userId']);
			}
		}
		$output = $output->get()
				->toArray();

		//Laravel's built-in hasOne functionality won't work on atoms
		$entityIds = array_column($output, 'atomEntityId');
		$atoms = Atom::findNewest($entityIds)
				->get()
				->toArray();
		foreach($output as &$row) {
			foreach($atoms as $atomKey => $atom) {
				if($atom['entityId'] == $row['atomEntityId']) {
					unset($atom['xml']);		//a waste of bandwidth in this case
					$row['atom'] = $atom;
					unset($atoms[$atomKey]);		//for performance
				}
			}
		}

		return $output;
	}
}
