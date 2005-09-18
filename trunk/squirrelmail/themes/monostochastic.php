<?php

/**
 * monostochastic.php
 * Name:    Monostochastic
 * Date:    October 20, 2001
 * Comment: Generates random two-color frames, featuring either
 *          a dark or light background.
 *
 * @author Jorey Bump
 * @copyright &copy; 2000-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage themes
 */

/** seed the random number generator */
sq_mt_randomize();

/** light(1) or dark(0) background toggle **/
$bg = mt_rand(0,1);

/** range delimiter **/
$bgrd = $bg * 128;

/** background **/
$cmin_b = 0 + $bgrd;
$cmax_b = 127 + $bgrd;

/** generate random color **/
$rb = mt_rand($cmin_b,$cmax_b);
$gb = mt_rand($cmin_b,$cmax_b);
$bb = mt_rand($cmin_b,$cmax_b);

/** text **/
$cmin_t = 128 - $bgrd;
$cmax_t = 255 - $bgrd;

/** generate random color **/
$rt = mt_rand($cmin_t,$cmax_t);
$gt = mt_rand($cmin_t,$cmax_t);
$bt = mt_rand($cmin_t,$cmax_t);

/** set array element as hex string with hashmark (for HTML output) **/
for ($i = 0; $i <= 15; $i++) {
    if ($i == 0 or $i == 3 or $i == 4 or $i == 5 or $i == 9 or $i == 10 or $i == 12) {
        $color[$i] = sprintf('#%02X%02X%02X',$rb,$gb,$bb);
    } else {
        $color[$i] = sprintf('#%02X%02X%02X',$rt,$gt,$bt);
    }
}

/* Reference from  http://www.squirrelmail.org/wiki/CreatingThemes

$color[0]   = '#xxxxxx';  // Title bar at the top of the page header
$color[1]   = '#xxxxxx';  // Not currently used
$color[2]   = '#xxxxxx';  // Error messages (usually red)
$color[3]   = '#xxxxxx';  // Left folder list background color
$color[4]   = '#xxxxxx';  // Normal background color
$color[5]   = '#xxxxxx';  // Header of the message index
                          // (From, Date,Subject)
$color[6]   = '#xxxxxx';  // Normal text on the left folder list
$color[7]   = '#xxxxxx';  // Links in the right frame
$color[8]   = '#xxxxxx';  // Normal text (usually black)
$color[9]   = '#xxxxxx';  // Darker version of #0
$color[10]  = '#xxxxxx';  // Darker version of #9
$color[11]  = '#xxxxxx';  // Special folders color (INBOX, Trash, Sent)
$color[12]  = '#xxxxxx';  // Alternate color for message list
                          // Alters between #4 and this one
$color[13]  = '#xxxxxx';  // Color for quoted text -- > 1 quote
$color[14]  = '#xxxxxx';  // Color for quoted text -- >> 2 or more

*/

?>