<?php

namespace App;

/**
 * A standardized way to log admin activity.
 */
class AdminLog {
    const FILENAME = 'admin.log';

    /**
     * Write a message to the admin activity log.
     *
     * @param string $message The message you want to record
     *
     * @return void
     */
    public static function write($message) {
        $filename = storage_path('logs/' . self::FILENAME);
        $date = date('r');
        $formattedMessage = '[' . $date . "]\t" . $message . "\n";

        $handle = fopen($filename, 'a');
        fwrite($handle, $formattedMessage);
        fclose($handle);
    }
}