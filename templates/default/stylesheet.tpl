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
.leftrecent {
    font-weight:bold;
}
.leftnoselect a:link, .leftnoselect a:visited, .leftnoselect a:hover, .leftnoselect a:active {
    color:  <?php echo SQM_TEXT_HIGHLIGHT; ?>;
}

/* highlighted texts */
.highlight {
    color:  <?php echo SQM_TEXT_HIGHLIGHT; ?>;
}

/* left_main.tpl definitions */
.sqm_wrapperTable   {
    border:0;
    padding:0;
    margin-left:0;
    border-spacing:0;
    width:99%
}
sqm_leftMain table {
    border:0;
    padding:0;
    margin:0;
    border-spacing:0;
}
.sqm_folderHeader {
    font-size:18px;
    font-weight:bold;
    text-align:center;
}
.sqm_clock {
}
.sqm_lastRefreshTime {
    white-space: nowrap;
}
.sqm_refreshButton {
}

/* formating of error template */
.thead_caption {
    font-weight: bold;
    text-align: center;
}

.error_list {
}
.error_table {
    color: <?php echo $color[14]; ?>;
    border: 2px solid <?php echo $color[0]; ?>;
    background-color: <?php echo $color[3]; ?>;
    width: 100%;
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
    width: 80%;
    border: 2px solid <?php echo $color[0]; ?>;

}
.error_key {
    width: 20%;
    border: 2px solid <?php echo $color[0]; ?>;
    color: <?php echo $color[14]; ?>;
    font-weight: bold;
    font-style: italic;
    background-color: <?php echo $color[0]; ?>;
}

/* form fields */
input.sqmtextfield{
}
input.sqmpwfield {
}
input.sqmcheckbox {
}
input.sqmradiobox {
}
input.sqmhiddenfield {
}
input.sqmsubmitfield {
}
input.sqmresetfield {
}
input.sqmtextarea {
}

/* basic definitions */
.table_empty {
    width:100%;
    border:0;
    margin:0;
    padding:0;
    border-spacing:0;
}

.table_standard {
    width:100%;
    border:1px solid <?php echo $color[0]; ?>;
    padding:0;
    margin:0;
    border-spacing:0;
}

em		{
    font-weight:bold;
    font-style:normal;
}

small	{
    font-size:80%;
}
img   {
    border:0;
}

/* login.tpl definitions */
#sqm_login table {
    border:0;
    margin:0;
    padding:0;
    border-spacing:0;
    margin-left:auto;
    margin-right:auto;
}
#sqm_login td {
    padding:2px;
}

.sqm_loginImage {
    margin-left:auto;
    margin-right:auto;
    padding:2px;
}
.sqm_loginTop {
    text-align:center;
    font-size:80%;
}
.sqm_loginOrgName {
    font-weight:bold;
    text-align:center;
    background: <?php echo $color[0]; ?>;
    width:350px;
    border:0;
}
.sqm_loginFieldName {
    text-align:right;
    width:30%;
}
.sqm_loginFieldInput {
    text-align:left;
}
.sqm_loginSubmit {
    text-align:center;
}

/* note.tpl defs */
.sqm_noteWrapper {
    text-align:center;
    width:100%;
}
.sqm_note {
    margin-left:auto;
    margin-right:auto;
    font-weight:bold;
    text-align:center;
}

/* motd.tpl defs */
.sqm_motdWrapper {
    text-align:center;
    width:100%;
    margin:1px;
}
.sqm_motd {
    margin-left:auto;
    margin-right:auto;
    text-align:center;
    background: <?php echo $color[9]; ?>;
    width:70%;
    padding:0;
}
.sqm_motd td {
    text-align:center;
    background: <?php echo $color[4]; ?>;
    padding:5px;
}

/* empty_folder.tpl defs */
.sqm_emptyFolderWrapper {
    text-align:center;
    width:100%;
}
.sqm_emptyFolder {
    margin-left:auto;
    margin-right:auto;
    text-align:center;
    background: <?php echo $color[9]; ?>;
    padding:1px;
    width:100%;
}
.sqm_emptyFolder td {
    text-align:center;
    background: <?php echo $color[4]; ?>;
    padding-top:15px;
    padding-bottom:15px;
}

/* error_box.tpl definitions */
.table_errorBoxWrapper   {
    width:100%;
    padding:0;
    border-spacing:0;
    border:0;
    text-align:center;
    margin-left:auto;
    margin-right:auto;
    background: <?php echo $color[9]; ?>;
}

.table_errorBox  {
    width:100%;
    padding:0;
    border-spacing:0;
    border:0;
    text-align:center;
    margin-left:auto;
    margin-right:auto;
    background: <?php echo $color[0]; ?>;
}
.error_header {
    color: red;
    font-weight:bold;
    font-weight:bold;
    font-style:normal;
}
.error_message {
    background: <?php echo $color[4]; ?>;
}

/* error_logout.tpl definitions */
#sqm_errorLogout {
    width:100%;
    text-align:center;
}
#sqm_errorLogout table {
    border:0;
    margin:0;
    padding:0;
    border-spacing:0;
    margin-left:auto;
    margin-right:auto;
}
#sqm_errorLogout td {
    padding:2px;
}
.sqm_errorLogoutImage {
    margin-left:auto;
    margin-right:auto;
    padding:2px;
}
.sqm_errorLogoutTop {
    text-align:center;
    font-size:80%;
}

/* page_header.tpl definitions */
.sqm_currentFolder	{
    background: <?php echo $color[9]; ?>;
    padding:2px;
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}
.sqm_headerSignout	{
    background: <?php echo $color[9]; ?>;
    padding:2px;
    text-align: <?php echo SQM_ALIGN_RIGHT; ?>;
    font-weight:bold;
}
.sqm_topNavigation	{
    padding:2px;
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}
.sqm_providerInfo	{
    padding:2px;
    text-align: <?php echo SQM_ALIGN_RIGHT; ?>;
}

/* message_list.tpl definitions */
.table_messageListWrapper	{
    width:100%;
    padding:0;
    border-spacing:0;
    border:0;
    text-align:center;
    margin-left:auto;
    margin-right:auto;
    background: <?php echo $color[9]; ?>;
}

.table_messageList	{
    width:100%;
    padding:0;
    border-spacing:0;
    border:0;
    text-align:center;
    margin-left:auto;
    margin-right:auto;
    background: <?php echo $color[5]; ?>;
}

.table_messageList	a	{
    white-space:nowrap;
}

.table_messageList	tr.headerRow	{
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
    white-space:nowrap;
    font-weight:bold;
}
.table_messageList td.spacer {
    height:1px;
    background: <?php echo $color[0]; ?>;
}

.table_messageList	tr		{
    vertical-align:top;
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

.table_messageList	td	{
    white-space:nowrap;
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
.table_messageList	td.col_date	{
    text-align:center;
}
.table_messageList	td.col_text	{
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}

.unread		{
    font-weight:bold;
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

.col_checked	{
}

.links_paginator			{
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
}

.message_count				{
    text-align:right;
    font-size:8pt;
}

.message_list_controls {
    background: <?php echo $color[0]; ?>;
}

.message_control_button {
    padding:0px;
    margin:0px;
}
.message_control_buttons {
    text-align: <?php echo SQM_ALIGN_LEFT; ?>;
    font-size:10px;		/* replaces <small> tags to allow greater control of fonts w/ using an id. */
}
.message_control_delete {
    text-align: <?php echo SQM_ALIGN_RIGHT; ?>;
    font-size:10px;		/* replaces <small> tags to allow greater control of fonts w/ using an id. */
}
.message_control_move {
    text-align: <?php echo SQM_ALIGN_RIGHT; ?>;
    font-size:10px;		/* replaces <small> tags to allow greater control of fonts w/ using an id. */
}

.spacer	{
    height:5px;
    background: <?php echo $color[4]; ?>;
}


