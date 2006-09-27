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
/* advacned login page defs */
#sqm_login  {
    margin-top: 25px;
    text-align: center;
}

#sqm_login  table   {
    border: 0;
    padding: 0;
    margin-left: auto;
    margin-right: auto;
    width: auto;
}

#sqm_login  td   {
    padding-left: 2px;
    padding-right: 2px;
    padding-top: 0px;
    padding-bottom: 0px;
    color: #726b58;
    font-family: verdana, sans-serif;
    width: auto;
    text-align: center;
}

#sqm_login  td.orgName {
    font-weight: bold;
    background: none;
    font-size: 90%;
}

#sqm_login  td.orgLogo {
    width: 155px;
    text-align: center;
    vertical-align: center;
}

#sqm_login  td.orgLogo  img {
    width: 150px;
    padding:0;
}

#sqm_login  td.attr   {
    font-size: 70%;
    padding-top:5px;
    padding-bottom: 10px;
}

#sqm_login  td.fieldName {
    font-size: 10pt;
    font-weight: bold;
    text-align: <?php echo SQM_ALIGN_RIGHT; ?>;
    width: 50%;
}

#sqm_login  td.fieldInput {
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
    padding-top: 1px;
    padding-bottom: 1px;
}

#sqm_login  td.loginSubmit {
    padding-top: 15px;
}

#sqm_login  input.input {
    font-size: 80%;
    color: #110f08;
    border: 1px solid #726b58;
    padding: 1px;
    background: url('<?php echo $sTplDir; ?>images/login2.png') repeat-y;
    width: 160px;
}

/* advanced option order defs */
#optionHighlight    td.divider  {
    border-top: 1px solid <?php echo $color[0]; ?>;
    border-bottom: 1px solid <?php echo $color[0]; ?>;
    background: <?php echo $color[0]; ?>;
    font-weight: bold;
    padding-top: 2px;
    padding-bottom: 2px;
}

/* advanced message editing defs */
#colorSample    {
    width: 50px;
    padding-left:10px;
}

#optionHighlightAdd table.colorTable   {
    margin-left: 0;
    margin-top: 2px;
}

#optionHighlightAdd td.fieldValue   {
    font-size: 75%;
    font-weight: bold;
}

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