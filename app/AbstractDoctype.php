<?php

namespace App;

abstract class AbstractDoctype {
    protected $_config = [
        'validRootElements' => [],       //in order of descending priority
        'validTitleElements' => [],       //in order of descending priority
        'idPrefixes' => [],     //structure is tagName => prefix
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
            preg_match('#<' . $titleElement . '(\s+[^>]*)?>(.*?)</' . $titleElement . '>#Ssi', $xml, $match);

            if($match) {
                return trim($match[2]);
            }
        }

        return null;
    }

    /**
     * Assign IDs to XML elements where appropriate. Optionally adds specified entityId to first element.
     *
     * @param string $xml The XML to operate on
     * @param ?string $entityId (optional) Add this entityId to the first element
     *
     * @return string The modified XML
     */
    public function assignXMLIds($xml, $entityId = null) {
        $idPrefixes = $this->getConfig()['idPrefixes'];

        $tagRegex = '/<[^\/<>]+>/S';
        $nameRegex = '/<([^\s<>]+).*?>/S';
        $idSuffixRegex = '/\bid="[^"]*?(\d+)"/Si';
        $idReplaceableSuffixRegex = '/_REPLACE_ME__/S';
        $idRegex = '/\bid="[^"]*"/Si';

        //remove empty ids
        $xml = str_replace(' id=""', '', $xml);

        //remove the first id -- it will be added during export
        $xml = self::removeAtomIDFromXML($xml);

        //initialize $idSuffix
        $idSuffix = 0;
        preg_match_all($tagRegex, $xml, $tags);
        $tags = $tags[0];
        foreach($tags as $key => $tag) {
            //skip the tags we don't care about
            $name = strtolower(preg_replace($nameRegex, '$1', $tag));
            if(!isset($idPrefixes[$name])) {
                unset($tags[$key]);
                continue;
            }

            preg_match($idSuffixRegex, $tag, $id);
            if($id) {
                $id = (int)$id[1];
                $idSuffix = $idSuffix > $id ? $idSuffix : $id;
            }
        }

        //complete id replacements
        $old = '';
        $new = $xml;
        while($old != $new) {
            $old = $new;
            $new = preg_replace($idReplaceableSuffixRegex, ++$idSuffix, $old, 1);
        }
        if($old) {
            --$idSuffix;
        }
        $xml = $new;

        //assign the missing ids
        foreach($tags as $tag) {
            if(preg_match($idRegex, $tag)) {
                continue;       //it already has an id
            }

            $name = strtolower(preg_replace($nameRegex, '$1', $tag));
            $prefix = $idPrefixes[$name];
            $id = $prefix . ++$idSuffix;
            $newTag = substr($tag, 0, strlen($tag) - 1) . ' id="' . $id . '">';
            $tag = preg_quote($tag, '/');
            $xml = preg_replace('/' . $tag . '/', $newTag, $xml, 1);
        }

        if($entityId === null) {
            //yes, we need to do this again in order to keep automatic IDs from creeping in
            $xml = self::removeAtomIDFromXML($xml);
        }
        else {
            $xml = $this->addEntityIdToXML($xml, $entityId);
        }

        return trim($xml);
    }

    /**
     * Adds the specified entityId to the first element.
     *
     * @param string $xml The XML to operate on
     * @param string $entityId The atom's entityId
     *
     * @return string The modified XML
     */
    public function addEntityIdToXML($xml, $entityId) {
        $idPrefixes = $this->getConfig()['idPrefixes'];
        $xml = self::removeAtomIDFromXML($xml);
        preg_match('/^\s*<(\w+)/', $xml, $match);
        $firstTag = strtolower($match[1]);
        $id = $idPrefixes[$firstTag] . $entityId;
        $xml = preg_replace('/^\s*<([^>]+)/i', '<$1 id="' . $id . '"', $xml);

        return $xml;
    }

    /**
     * Attempt to find the atom's entityId in its XML
     *
     * @param string $xml The XML to operate on
     *
     * @return ?string The detected entityId
     */
    public static function detectAtomIDFromXML($xml) {
        $idPrefixes = $this->getConfig()['idPrefixes'];
        $prefixPartial = '(' . implode('|', $idPrefixes) . ')';
        preg_match('/^(\s*<[^>]*) id="' . $prefixPartial . '([^"]*)"/Si', $xml, $match);

        return (isset($match[3]) && $match[3] != '_REPLACE_ME__') ? $match[3] : null;
    }

    /**
     * Attempt to remove the atom's entityId from its XML
     *
     * @param string $xml The XML to operate on
     *
     * @return ?string The detected entityId
     */
    public static function removeAtomIDFromXML($xml) {
        return preg_replace('/^(\s*<[^>]*) id="[^"]*"/Si', '$1', $xml);
    }
}
