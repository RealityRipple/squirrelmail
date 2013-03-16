<?php

/**
 * Name: Solarized Light
 * Date: 27 Feb 2013
 *
 * @author Pavneet Arora
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: plain_blue_theme.php 14084 2013-02-27 09:14:03Z pdontthink $
 * @package squirrelmail
 * @subpackage themes
 */

/*
 * This theme is based on Ethan Schoonover's Solarized palette.
 *
 * Details may be found at http://ethanschoonover.com/solarized
 *
 * SOLARIZED   HEX   16/8 TERMCOL
 * --------- ------- ---- ------- ----------- ---------- ----------- -----------
 * base03    #002b36 8/4  brblack
 * base02    #073642 0/4  black
 * base01    #586e75 10/7 brgreen
 * base00    #657b83 11/7 bryellow
 * base0     #839496 12/6 brblue
 * base1     #93a1a1 14/4 brcyan
 * base2     #eee8d5 7/ 7 white 254
 * base3     #fdf6e3 15/7 brwhite
 * yellow    #b58900 3/3  yellow
 * orange    #cb4b16 9/3  brred
 * red       #dc322f 1/1  red
 * magenta   #d33682 5/5  magenta
 * violet    #6c71c4 13/5 brmagenta
 * blue      #268bd2 4/4  blue
 * cyan      #2aa198 6/6  cyan
 * green     #859900 2/2  green
*/

global $color;
$color[0]   = '#586e75'; // Title bar at the top of the page header.
$color[1]   = '#800000'; // Error messages border, usually red.
$color[2]   = '#dc322f'; // Error messages, usually red.
$color[3]   = '#fdf6e3'; // Left folder list background color.
$color[4]   = '#eee8d5'; // Normal background color.
$color[5]   = '#073642'; // Header of the message index (From, Date, Subject).
$color[6]   = '#859900'; // Normal text on the left folder list.
$color[7]   = '#657b83'; // Links in the right frame.
$color[8]   = '#839496'; // Normal text.
$color[9]   = '#073642'; // Darker version of #0.
$color[10]  = '#376589'; // Darker version of #9.
$color[11]  = '#b58900'; // Special folders color (Inbox, Trash, Sent).
$color[12]  = '#fdf6e3'; // Alternate color for message list (alters between #4 and this one).
$color[13]  = '#770000'; // Color for single-quoted text ("> text") when reading.
$color[14]  = '#770000'; // Color for text with more than one quote.
$color[15]  = '#001166'; // Non-selectable folders in the left frame.
$color[16]  = '#001166'; // Highlight color (since SquirrelMail 1.5.1, default: $color[2])

