<?php
/**
 * SquirrelMail CSS template for additional definitions needed by the advanced
 * template
 *
 * Template is used by style.php script to generate css file used by
 * SquirrelMail scripts.
 *
 * Available constants
 *
 * Color codes used by selected theme:
 * <ul>
 *   <li>SQM_BACKGROUND - background color
 *   <li>SQM_BACKGROUND_LEFT - background of folder tree
 *   <li>SQM_TEXT_STANDARD - text color
 *   <li>SQM_TEXT_STANDARD_LEFT - text color of folder tree
 *   <li>SQM_LINK - color of links
 *   <li>SQM_LINK_LEFT - color of links in folder tree
 *   <li>SQM_TEXT_SPECIAL - color of special folder links in folder tree
 *   <li>todo: other constants should be documented here
 * </ul>
 *
 * Optional template variables
 * <ul>
 *   <li>fontfamily - string with list of fonts used by selected style.
 *   <li>fontsize - integer with selected font size value.
 * </ul>
 * Variables are set to empty string, when value is not set.
 *
 * @copyright &copy; 2005-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/* retrieve the template vars */
extract($t);
?>

/* Advanced Tree definitions */
.dtree {
       font-size:11px;
       white-space:nowrap;
}
.dtree p {
    margin-top:12px;
    margin-bottom:2px;
    padding-bottom:4px;
    text-align:center;
    overflow: hidden;
}
.dtree a:hover {
    text-decoration: underline;
}
.dtree a {
    text-decoration:none;
}
.dtree img {
    border:0;
    vertical-align: middle;
}
.dtree a.node, .dtree a.nodeSel {
    white-space: nowrap;
    padding: 1px 2px 1px 2px;
}
.dtree a.node:hover, .dtree a.nodeSel:hover {
    color: <?php echo SQM_TEXT_HIGHLIGHT; ?>;
}
.dtree a.nodeSel {
    color: <?php echo SQM_TEXT_HIGHLIGHT; ?>;
}
.dtree .clip {
    overflow: hidden;
}