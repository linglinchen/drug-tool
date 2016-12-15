<?php

namespace App;

abstract class AbstractDoctype {
    protected $_config = [
        'validRootElements' => [],       //in order of descending priority
        'validTitleElements' => [],       //in order of descending priority
    ];

    /**
     * Get this doctype's config.
     *
     * @return mixed[]
     */
    public function getConfig() {
        return $this->_config;
    }

    /**
     * Detect an atom's title from its XML.
     *
     * @param string $xml
     *
     * @return ?string
     */
    public function detectTitle($xml) {
        $validTitleElements = $this->getConfig()['validTitleElements'];

        foreach($validTitleElements as $titleElement) {
            preg_match('#<' . $titleElement . '(\b\s+[^>]*)?>(.*?)</' . $titleElement . '>#Si', $xml, $match);

            if($match) {
                return trim($match[2]);
            }
        }

        return null;
    }
}
