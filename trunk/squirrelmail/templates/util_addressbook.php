<?php
/**
 * util_addressbook.php
 *
 * Functions to make working with address books easier
 * 
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/**
 * Display a column header with sort buttons
 * 
 * @param string $field which field to display
 * @author Steve Brown
 * @since 1.5.2
 */
function addAbookSort ($field) {
    global $abook_sort_order;
    
    switch ($field) {
        case 'nickname':
            $str = _("Nickname");
            $alt = _("sort by nickname");
            $down = 0;
            $up = 1;
            $has_sort = true;
            break;
        case 'fullname':
            $str = _("Name");
            $alt = _("sort by name");
            $down = 2;
            $up = 3;
            $has_sort = true;
            break;
        case 'email':
            $str = _("E-mail");
            $alt = _("sort by email");
            $down = 4;
            $up = 5;
            $has_sort = true;
            break;
        case 'info':
            $str = _("Info");
            $alt = _("sort by info");
            $down = 6;
            $up = 7;
            $has_sort = true;
            break;
        default:
            return 'BAD SORT FIELD GIVEN: "'.$field.'"';
    }
    
    return $str . ($has_sort ? show_abook_sort_button($abook_sort_order, $alt, $down, $up) : '');
}

/**
 * Create a link to compose an email to the email address given.
 * 
 * @param array $row contact as given to the addressbook_list.tpl template
 * @author Steve Brown
 * @since 1.5.2
 */
function composeLink ($row) {
    return makeComposeLink('src/compose.php?send_to=' .
                           rawurlencode($row['FullAddress']),
                           htmlspecialchars($row['Email']));
}
?>