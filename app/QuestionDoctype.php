<?php

namespace App;

use App\AbstractDoctype;

use App\Atom;

class QuestionDoctype extends AbstractDoctype {
    protected $_config = [
        'validAtomRootElements' => ['question', 'label', 'title'],
        'validTitleElements' => ['qnum'],
        'idPrefixes' => [
            'question' => 'q',
            'label' => 'l',
        ],
        'chapterElement' => [
            'elementXpath' => '//chapter',
            'keyAttributeName' => 'number',
        ]
    ];

    public function beforeSave($atom) {
         $originalAtom = Atom::findNewestIfNotDeleted($atom->entity_id, $atom->product_id);
         if ($originalAtom){ // for existing atom
            $originalDomainCode = $originalAtom->domain_code;
            if($originalDomainCode != $atom->domain_code) {
                $replacement = '$1' . $atom->domain_code . '$3';
                $atom->xml = preg_replace('/(<content_area><entry>)(.*)(<\/entry><\/content_area>)/Ssi', $replacement, $atom->xml);
            }
            else {
                preg_match('/<content_area><entry>(.*)<\/entry><\/content_area>/Si', $atom->xml, $matches);
                if($matches) {
                    $atom->domain_code = trim($matches[1]);
                }
            }
        } //if it's new atom, just return true
        return true;
    }

    /**
     * Detect an atom's title.
     *
     * @param object $atom
     *
     * @return ?string
     */
    public function detectTitle($atom) {
        return $atom->title ? $atom->title : $this->_getNewQuestionTitle($atom->product_id);
    }

    /**
     * Decide what the new question's title will be (find the max of the existing question title)
     *
     * @param integer $productId Limit to this product
     *
     * @return string
     */
    protected function _getNewQuestionTitle($productId) {
        $titles = array_map(
            function ($title) {
                return (int)$title;
            },
            Atom::select('alpha_title')
                ->where('product_id', '=', $productId)
                ->whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })
                ->get()
                ->pluck('alpha_title')
                ->toArray()
        );

        return (string)(max($titles) + 1);
    }
}