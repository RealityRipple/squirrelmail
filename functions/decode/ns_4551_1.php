<?php

/**
 * functions/decode/ns_4551_1.php
 *
 * Copyright (c) 2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file contains ns_4551-1 decoding function that is needed to read
 * ns_4551-1 encoded mails in non-ns_4551-1 locale.
 *
 * This is the same as ISO-646-NO and is used by some
 * Microsoft programs when sending Norwegian characters 
 *
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
    return strtr ($string, "[\\]{|}", "ÆØÅזרו");
}
?>