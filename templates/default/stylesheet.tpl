<?php

/**
 * SquirrelMail CSS template
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
//return false;
?>
/* older css template */
body, td, th, dd, dt, h1, h2, h3, h4, h5, h6, p, ol, ul, li {
<?php
if($fontfamily) echo '  font-family: '.$fontfamily.";\n";
?>
}
body, small {
<?php
if($fontsize) echo '  font-size: '.($fontsize-2)."pt;\n";
?>
}
td, th {
<?php
if($fontsize) echo '  font-size: '.$fontsize."pt;\n";
?>
}
textarea, pre {
font-family: monospace;
<?php
if($fontsize) echo '  font-size: '.($fontsize-1)."pt;\n";
?>
}

/* page body formatting */
body {
    color:  <?php echo SQM_TEXT_STANDARD; ?>;
    background-color: <?php echo SQM_BACKGROUND; ?>;
}
body.sqm_leftMain {
    color:  <?php echo SQM_TEXT_STANDARD_LEFT; ?>;
    background-color: <?php echo SQM_BACKGROUND_LEFT; ?>;
    text-align: left;
}

/* right links */
a:link, a:visited, a:hover, a:active {
    color: <?php echo SQM_LINK; ?>;
}

/* left links */
.sqm_leftMain a:link, .sqm_leftMain a:visited, .sqm_leftMain a:hover, .sqm_leftMain a:active {
    color:  <?php echo SQM_LINK_LEFT; ?>;
}

.leftunseen, .leftspecial, .leftspecial a:link, .leftspecial a:visited, .leftspecial a:hover, .leftspecial a:active {
    color:  <?php echo SQM_TEXT_SPECIAL; ?>;
}

.leftnoselect a:link, .leftnoselect a:visited, .leftnoselect a:hover, .leftnoselect a:active {
    color:  <?php echo SQM_TEXT_HIGHLIGHT; ?>;
}

/* highlighted texts */
.highlight {
    color:  <?php echo SQM_TEXT_HIGHLIGHT; ?>;
}

.error_table {
    color: <?php echo $color[14]; ?>;
    border: 2px solid <?php echo $color[0]; ?>;
    background-color: <?php echo $color[3]; ?>;
}
.error_thead {
    background-color: <?php echo $color[10]; ?>;
}
.error_thead_caption {
    background-color: <?php echo $color[10]; ?>;
}
.error_row {
    color: <?php echo $color[14]; ?>;
}
.error_val {
    color: <?php echo $color[8]; ?>;
    border: 2px solid <?php echo $color[0]; ?>;

}
.error_key {
    border: 2px solid <?php echo $color[0]; ?>;
    color: <?php echo $color[14]; ?>;
    background-color: <?php echo $color[0]; ?>;
}


td.header1  {
    background: <?php echo $color[0]; ?>;
    text-align: center;
    font-weight: bold;
}


td.header2  {
    background: <?php echo $color[9]; ?>;
    text-align: center;
    font-weight: bold;
    padding-top: 4px;
    padding-bottom: 4px;
}

.table_standard {
    border:1px solid <?php echo $color[0]; ?>;
}

.sqm_loginOrgName, .sqm_signoutBar {
    background: <?php echo $color[0]; ?>;
}

.sqm_signout {
    margin-top: 2em;
    text-align: center;
}

.sqm_motd {
    background: <?php echo $color[9]; ?>;
}
.sqm_motd td {
    background: <?php echo $color[4]; ?>;
}

/* empty_folder.tpl defs */
.sqm_emptyFolder {
    background: <?php echo $color[9]; ?>;
}
.sqm_emptyFolder td {
    background: <?php echo $color[4]; ?>;
}

/* error_box.tpl definitions */
.table_errorBoxWrapper   {
    background: <?php echo $color[9]; ?>;
}

.table_errorBox  {
    background: <?php echo $color[0]; ?>;
}
.error_message {
    background: <?php echo $color[4]; ?>;
}

/* page_header.tpl definitions */
.sqm_currentFolder	{
    background: <?php echo $color[9]; ?>;
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}
.sqm_headerSignout	{
    background: <?php echo $color[9]; ?>;
    text-align: <?php echo SQM_ALIGN_RIGHT; ?>;
}
.sqm_topNavigation	{
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}
.sqm_providerInfo	{
    text-align: <?php echo SQM_ALIGN_RIGHT; ?>;
}

/* message_list.tpl definitions */
.table_messageListWrapper	{
    background: <?php echo $color[9]; ?>;
}

.table_messageList	{
    background: <?php echo $color[5]; ?>;
}

.table_messageList	tr.headerRow	{
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}
.table_messageList td.spacer {
    background: <?php echo $color[0]; ?>;
}
.table_messageList	tr.even	{
    background: <?php echo $color[12]; ?>;
}
.table_messageList	tr.odd	{
    background: <?php echo $color[4]; ?>;
}
.table_messageList	tr.mouse_over	{
    background: <?php echo $color[5]; ?>;
}
.table_messageList	tr.clicked	{
    background: <?php echo (!empty($color[16])) ? $color[16] : $color[2]; ?>;
}
.table_messageList	td.col_check	{
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}
.table_messageList	td.col_subject	{
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}
.table_messageList	td.col_flags	{
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}
.table_messageList	td.col_text	{
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}
.deleted	{
    color: <?php echo $color[9]; ?>;
}
.flagged	{
    color: <?php echo $color[2]; ?>;
}
.high_priority	{
    color: <?php echo $color[1]; ?>;
}
.low_priority	{
    color: <?php echo $color[8]; ?>;
}

.links_paginator			{
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}

.message_list_controls {
    background: <?php echo $color[0]; ?>;
}

.message_control_buttons {
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}
.message_control_delete {
    text-align: <?php echo SQM_ALIGN_RIGHT; ?>;
}
.message_control_move {
    text-align: <?php echo SQM_ALIGN_RIGHT; ?>;
}

.spacer	{
    background: <?php echo $color[4]; ?>;
}

/* folder_manip.tpl defs */
#folderManip   {
    text-align:center;
}

#folderManip   td  {
    text-align: center;
    padding: 2px;
}

#folderManip   table   {
    margin-left: auto;
    margin-right: auto;
    padding-top: 8px;
    padding-bottom: 8px;
    border: 0;
    width: 70%;
}

#folderManip   table.wrapper   {
    border: 1px solid <?php echo $color[0]; ?>;
    width: 95%;
    margin-left: auto;
    margin-right: auto;
    padding: 0;
}

#folderManip   td.folderAction {
    background: <?php echo $color[0]; ?>;
}

#folderManip    div {
    margin-left: auto;
    margin-right: auto;
    width: 80%;
    text-align: left;
}