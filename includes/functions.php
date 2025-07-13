<?php
// This file can be used for future functions.

/**
 * Censors the last 5 characters of a string.
 * If the string is shorter than 5 characters, it censors the whole string.
 *
 * @param string $string The input string to censor.
 * @return string The censored string.
 */
function censor_string($string) {
    $length = strlen($string);
    if ($length <= 5) {
        return str_repeat('*', $length);
    }
    return substr($string, 0, -5) . '*****';
}
?>