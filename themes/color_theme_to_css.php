#!/usr/bin/env php
<?php
/**
 * color_theme_to_css.php
 *
 * This script can be used to convert an old $color theme to a stylesheet for
 * use with templates.  Output is sent to STDOUT.
 * 
 * HOWTO:
 *      1. Create a .php file containing your $color theme.
 *      2. Run this script from a command line, giving the name of your theme file
 *         as an arguement to this script, e.g.:
 *
 *            /path/to/squirrelmail/templates/theme_to_css.php /path/to/mytheme.php
 * 
 *         To send the output to a .css file, do the following:
 *
 *            /path/to/squirrelmail/templates/theme_to_css.php /path/to/mytheme.php > my_theme.css
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 * @author Steve Brown
 * @since 1.5.2
 */

/** make sure that first command line argument is set */
if (empty($argv[1])) {
    echo "Please provide the path to the file containing the \$color theme you\n" .
         "wish to convert to a stylesheet.\n\n";
    exit (1);
}

$theme_file = $argv[1];
if (!is_file($theme_file) || !is_readable($theme_file)) {
    echo "The requested theme could not be converted because the file could not\n" .
         "be opened.  Please specify a theme file that can be read.\n\n";
    exit(1);
}

/* set default colors in case color theme is not full */
$def_color = array();
$def_color[0]   = '#dcdcdc'; // (light gray)     TitleBar
$def_color[1]   = '#800000'; // (red)
$def_color[2]   = '#cc0000'; // (light red)      Warning/Error Messages
$def_color[3]   = '#a0b8c8'; // (green-blue)     Left Bar Background
$def_color[4]   = '#ffffff'; // (white)          Normal Background
$def_color[5]   = '#ffffcc'; // (light yellow)   Table Headers
$def_color[6]   = '#000000'; // (black)          Text on left bar
$def_color[7]   = '#0000cc'; // (blue)           Links
$def_color[8]   = '#000000'; // (black)          Normal text
$def_color[9]   = '#ababab'; // (mid-gray)       Darker version of #0
$def_color[10]  = '#666666'; // (dark gray)      Darker version of #9
$def_color[11]  = '#770000'; // (dark red)       Special Folders color
$def_color[12]  = '#ededed'; // (light gray)     Alternate color for message list
$def_color[13]  = '#800000'; // (dark red)       Color for quoted text -- > 1 quote
$def_color[14]  = '#ff0000'; // (red)            Color for quoted text -- >> 2 or more
$def_color[15]  = '#002266'; // (dark blue)      Unselectable folders
$def_color[16]  = '#ff9933'; // (orange)         Highlight color

$color = $def_color;
include($theme_file);
if ($color === $def_color) {
    echo "The theme file you specified did not make any alterations to the default\n" .
         "color scheme.  Please choose a different file.\n\n";
    exit(1);
}

$css_source = <<<CSS
/* older css template */
/* page body formatting */
body {
    color:  __COLOR8__;
    background-color: __COLOR4__;
}
body.sqm_leftMain {
    color:  __COLOR6__;
    background-color: __COLOR3__;
}

/* right links */
a:link, a:visited, a:hover, a:active {
    color: __COLOR7__;
}

/* left links */
.sqm_leftMain a:link, .sqm_leftMain a:visited, .sqm_leftMain a:hover, .sqm_leftMain a:active {
    color:  __COLOR6__;
}
.leftunseen, .leftspecial, .leftspecial a:link, .leftspecial a:visited, .leftspecial a:hover, .leftspecial a:active {
    color:  __COLOR11__;
}
.leftnoselect a:link, .leftnoselect a:visited, .leftnoselect a:hover, .leftnoselect a:active {
    color:  __COLOR15__;
}

/* highlighted texts */
.highlight {
    color:  __COLOR15__;
}
.error_table {
    color: __COLOR14__;
    border: 2px solid __COLOR0__;
    background-color: __COLOR3__;
}
.error_thead {
    background-color: __COLOR10__;
}
.error_thead_caption {
    background-color: __COLOR10__;
}
.error_row {
    color: __COLOR14__;
}
.error_val {
    color: __COLOR8__;
    border: 2px solid __COLOR0__;

}
.error_key {
    border: 2px solid __COLOR0__;
    color: __COLOR14__;
    background-color: __COLOR0__;
}

/* Standard defs */
table.table1    {
    border: 1px solid __COLOR0__;
}
table.table2    {
    border: 1px solid __COLOR9__;
}
td.header1  {
    background: __COLOR0__;
}
td.header2  {
    background: __COLOR9__;
}
td.header4  {
    background: __COLOR5__;
}
tr.even {
    background: __COLOR12__;
}
tr.odd  {
    background: __COLOR4__;
}
.table_standard {
    border:1px solid __COLOR0__;
}

.sqm_loginOrgName, .sqm_signoutBar {
    background: __COLOR0__;
}
.sqm_motd {
    background: __COLOR9__;
}
.sqm_motd td {
    background: __COLOR4__;
}

/* empty_folder.tpl defs */
.sqm_emptyFolder {
    background: __COLOR9__;
}
.sqm_emptyFolder td {
    background: __COLOR4__;
}

/* error_box.tpl definitions */
.table_errorBoxWrapper   {
    background: __COLOR9__;
}
.table_errorBox  {
    background: __COLOR0__;
}
.error_message {
    background: __COLOR4__;
}

/* page_header.tpl definitions */
.sqm_currentFolder  {
    background: __COLOR9__;
}
.sqm_headerSignout  {
    background: __COLOR9__;
}

/* message_list.tpl definitions */
.table_messageListWrapper   {
    background: __COLOR9__;
}

.table_messageList  {
    background: __COLOR5__;
}
.table_messageList td.spacer {
    background: __COLOR0__;
}
.table_messageList  tr.mouse_over   {
    background: __COLOR5__;
}
.table_messageList  tr.clicked  {
    background: __COLOR16__;
}
.deleted    {
    color: __COLOR9__;
}
.flagged    {
    color: __COLOR2__;
}
.high_priority  {
    color: __COLOR1__;
}
.low_priority   {
    color: __COLOR8__;
}
.message_list_controls {
    background: __COLOR0__;
}
.spacer {
    background: __COLOR4__;
}

/* folder_manip.tpl defs */
#folderManip   table.wrapper   {
    border: 1px solid __COLOR0__;
}
#folderManip   td.folderAction {
    background: __COLOR0__;
}

/* addressbook_list.tpl defs */
#addressList    table   {
    border: 1px solid __COLOR9__;
}
#addressList    td.header1  {
    background: __COLOR9__;
}
#addressList    td.abookSwitch  {
    background: __COLOR0__;
}

#addressList    td.abookButtons  {
    background: __COLOR0__;
}
#addressList    td.abookField   {
    border-left: 1px solid __COLOR9__;
    border-right: 1px solid __COLOR9__;
}
#addressList    td.colHeader {
    background: __COLOR9__;
}
#addrBookSearch   table.wrapper   {
    border: 1px solid __COLOR9__;
}
#addrAddEdit    table   {
    border: 1px solid __COLOR9__;
}
#addrAddEdit    td.header   {
    background: __COLOR9__;
}

/* options defs */
#optionGroups   table   {
    border: 1px solid __COLOR0__;
}
#optionGroups   td.title    {
    background: __COLOR0__;
}
#optionGroups   td.optionElement    table   {
    border:1px solid __COLOR9__;
}
#optionGroups   td.optionName   {
    background: __COLOR9__;
}
#optionGroups   td.optionDesc   {
    background: __COLOR0__;
}
#optionDisplay  table   {
    border: 1px solid __COLOR0__
}
#optionOrder    table   {
    border: 1px solid __COLOR0__
}
#optionOrder    table.moveFields    td  {
    border-left: 1px solid __COLOR0__;
    border-right: 1px solid __COLOR0__;
}
#optionsIdentity    table.table2 tr  {
    background: __COLOR0__;
}
#optionsIdentity hr  {
    width: 95%;
    border: 1px solid __COLOR9__;
}

/* help defs */
#help   td.nav  {
    color: __COLOR0__;
}

/* search defs */
div.search td.header4  {
    border-bottom: 1px solid __COLOR9__;
}
div.search td.queryAction {
    border-left: 1px solid __COLOR9__;
    border-top: 1px solid __COLOR9__;
    border-bottom: 1px solid __COLOR9__;
}

div.search td.queryDesc  {
    border-top: 1px solid __COLOR9__;
    border-bottom: 1px solid __COLOR9__;
}
div.search span.error  {
    color: __COLOR2__;
}
div.search td.searchForm  {
    border-right: 1px solid __COLOR0__;
    border-left: 1px solid __COLOR0__;
}
div.search td.queryError {
    color: __COLOR2__;
}
div.search h2 {
    color: __COLOR2__;
}

/* compse defs */
div.compose tr.header {
    background: __COLOR9__;
}
div.compose tr.attachment td {
    background: __COLOR0__;
    border-top: 1px solid __COLOR9__;
    border-bottom: 1px solid __COLOR9__;
}

div.compose table.close {
    background: __COLOR0__;
    border:1px solid __COLOR9__;
}
CSS;

$p = array();
for ($k = 0; $k<=16; $k++) {
    $p[$k] = '__COLOR'.$k.'__';
    if (!isset($color[$k])) {
        $color[$k] = $def_color[$k];
    }
}

// Just to make sure...
ksort($p);
ksort($color);
$css_source = str_replace($p, $color, $css_source);
echo $css_source;
exit(0);
?>