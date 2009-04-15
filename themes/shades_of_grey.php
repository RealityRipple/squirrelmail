<?php

/**
 * Name:   Shades of Grey
 * Date:   October 20, 2001
 * Comment This theme generates random colors, featuring a
 *         light greyish background with dark text.
 *
 * @author Jorey Bump
 * @copyright &copy; 2000-2009 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage themes
 */

/** Prevent direct script loading */
if (isset($_SERVER['SCRIPT_FILENAME']) && $_SERVER['SCRIPT_FILENAME'] == __FILE__) {
    die();
}

for ($i = 0; $i <= 16; $i++) {
    /* background/foreground toggle */
    if ($i == 0 or $i == 3 or $i == 4 or $i == 5 or $i == 9 or $i == 10 or $i == 12 or $i == 16) {
        /* background */
        $r = mt_rand(176,255);
        $g = $r;
        $b = $r;
    } else {
        /* text */
        $cmin = 0;
        $cmax = 127;

        /** generate random color **/
        $r = mt_rand($cmin,$cmax);
        $g = mt_rand($cmin,$cmax);
        $b = mt_rand($cmin,$cmax);
    }

    /* set array element as hex string with hashmark (for HTML output) */
    $color[$i] = sprintf('#%02X%02X%02X',$r,$g,$b);
}
