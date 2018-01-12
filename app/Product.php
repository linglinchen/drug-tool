<?php

namespace App;

use App\AppModel;

use App\DrugDoctype;
use App\DictionaryDoctype;
use App\BookDoctype;
use App\QuestionDoctype;

class Product extends AppModel {
    protected $table = 'products';
    protected $guarded = ['id'];

    protected $doctypes = [
        'drug' => DrugDoctype::class,
        'dictionary' => DictionaryDoctype::class,
        'book' => BookDocype::class,
        'question' => QuestionDoctype::class,
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
}
