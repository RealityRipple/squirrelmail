<?php

/**
 * functions/decode/ns_4551_1.php
 *
 * This file contains ns_4551-1 decoding function that is needed to read
 * ns_4551-1 encoded mails in non-ns_4551-1 locale.
 *
 * This is the same as ISO-646-NO and is used by some
 * Microsoft programs when sending Norwegian characters
 *
 * @copyright 2004-2025 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage decode
 */

/**
 * ns_4551_1 decoding function
 *
 * @param string $string
 * @return string
 */
function charset_decode_ns_4551_1 ($string) {
    /*
     * These characters are:
     * Latin capital letter AE
     * Latin capital letter O with stroke
     * Latin capital letter A with ring above
     * and the same as small letters
     */
    return strtr ($string, "[\\]{|}", "������");
}
