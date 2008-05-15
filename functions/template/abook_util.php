<?php

/**
 * abook_util.php
 *
 * The following functions are utility functions for templates. Do not
 * echo output in these functions.
 *
 * @copyright &copy; 2005-2008 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */


/**
 * Display a column header with sort buttons
 *
 * @param string $field   Which field to display
 * @param int    $backend The abook backend to be shown when
 *                        sort link is clicked
 *
 * @author Steve Brown
 * @since 1.5.2
 */
function addAbookSort ($field, $backend) {
    global $abook_sort_order, $nbsp;

    switch ($field) {
        case 'nickname':
            $str = _("Nickname");
            $alt = _("Sort by nickname");
            $down = 0;
            $up = 1;
            $has_sort = true;
            break;
        case 'fullname':
            $str = _("Name");
            $alt = _("Sort by name");
            $down = 2;
            $up = 3;
            $has_sort = true;
            break;
        case 'email':
            $str = _("E-mail");
            $alt = _("Sort by email");
            $down = 4;
            $up = 5;
            $has_sort = true;
            break;
        case 'info':
            $str = _("Info");
            $alt = _("Sort by info");
            $down = 6;
            $up = 7;
            $has_sort = true;
            break;
        default:
            return 'BAD SORT FIELD GIVEN: "'.$field.'"';
    }

    // show_abook_sort_button() creates a hyperlink (using hyperlink.tpl) that encompases an image, using a getImage() call
    return $str . ($has_sort ? $nbsp . show_abook_sort_button($abook_sort_order, $alt, $down, $up, array('new_bnum' => $backend)) : '');
}


