<?php

namespace App;

use App\AppModel;

use App\BookDoctype;
use App\DictionaryDoctype;
use App\DrugDoctype;
use App\QuestionDoctype;
use App\XHTMLDoctype;

use App\Assignment;

class Product extends AppModel {
	protected $table = 'products';
	protected $guarded = ['id'];

	protected $doctypes = [
		'book' => BookDoctype::class,
		'dictionary' => DictionaryDoctype::class,
		'drug' => DrugDoctype::class,
		'question' => QuestionDoctype::class,
		'xhtml' => XHTMLDoctype::class,
	];

    /**
     * Gets this product's doctype.
     *
     * @return object
     */
    public function getDoctype() {
        $doctypeKey = $this->doctype;

        if(!isset($this->doctypes[$doctypeKey])) {
            throw new \Exception('Unknown doctype: ' . $doctypeKey);
        }

        return new $this->doctypes[$doctypeKey];
    }

    /**
     * Gets this product's current edition.
     *
     * @return object
     */
    public function getEdition() {
        return $this->current_edition;
    }

    /**
     * Gets this product's termtype.
     *
     * @return object
     */
    public function getTermtype() {
        return $this->termtype;
    }

    /**
     * Gets this product's categorytype.
     *
     * @return object
     */
    public function getCategorytype() {
        return $this->categorytype;
    }

    /**
     * Get a list of all products that the user has open assignments in.
     *
     * @param integer $userId The user to check
     *
     * @return object[] Products with open assignments
     */
    public static function withOpenAssignments($userId) {
        return Product::select('products.*')
                ->where('assignments.user_id', '=', $userId)
                ->join('atoms', 'atoms.product_id', '=', 'products.id')
                ->join('assignments', 'atoms.entity_id', '=', 'assignments.atom_entity_id')
                ->whereNotNull('assignments.task_end')
                ->groupBy('products.id')
                ->orderBy('products.title', 'ASC')
                ->get();
    }
}