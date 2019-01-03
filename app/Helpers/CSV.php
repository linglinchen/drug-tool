<?php

namespace App\Helpers;

/**
 * Generates CSVs.
 */
class CSV {
    /**
     * Generate a CSV from an array containing rows of associative arrays.
     *
     * @param array[] $data The data you want to turn into a CSV
     *
     * @return string Your CSV data
     */
    public static function generateCSV($data) {
        ob_start();

        //write the column headers
        if($data) {
            $row = self::_asArray(reset($data));
            echo self::_buildRow(array_keys($row)), "\n";
        }

        foreach($data as $row) {
            echo self::_buildRow(self::_asArray($row)), "\n";
        }

        return ob_get_clean();
    }

    /**
     * Generate a CSV line from the row.
     *
     * @param mixed[] $row A row of data
     *
     * @return string CSV output
     */
    protected static function _buildRow($row) {
        $rowOut = [];

        foreach($row as $cell) {
            $type = gettype($cell);
            if($type == 'boolean') {
                $rowOut[] = '"' . ($cell ? 'true' : 'false') . '"';
            }
            elseif(in_array($type, ['integer', 'double', 'single', 'NULL'])) {
                $rowOut[] = $cell;
            }
            elseif($type == 'string') {
                $rowOut[] = '"' . str_replace('"', '\\"', $cell) . '"';
            }
        }

        return implode(',', $rowOut);
    }

    /**
     * Ensure that the input is an array.
     *
     * @param mixed $input The row or whatever
     *
     * @return array Your shiny, new array
     */
    protected static function _asArray($input) {
        if(gettype($input) == 'object' && is_subclass_of($input, 'Illuminate\Database\Eloquent\Model')) {
            return $input->toArray();
        }

        return (array)$input;
    }
}