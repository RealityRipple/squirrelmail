<?php

/**
 * Style sheet script
 *
 * Script processes GET arguments and generates CSS output from stylesheet.tpl,
 * which is defined in each template set.
 *
 * Used GET arguments:
 * <ul>
 *   <li>themeid - string, sets theme file from themes/*.php
 *   <li>templateid - string, sets template set ID
 *   <li>fontset - string, sets selected set of fonts from $fontsets array.
 *   <li>fontsize - integer, sets selected font size
 *   <li>dir - string, sets text direction variables. Possible values 'rtl' or 'ltr'
 * </ul>
 * @copyright 2005-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This is the style page */
define('PAGE_NAME', 'style');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* safety check for older config.php */
if (!isset($fontsets) || !is_array($fontsets)) {
    $fontsets=array();
}


/**
 * The following code should no longer be neccesary, but it will remain for the
 * time being, just in case.
 *
 * TODO: Remove if no longer needed.
 **/
/* set default colors in case color theme is not full */
$color = array();
$color[0]   = '#dcdcdc'; // (light gray)     TitleBar
$color[1]   = '#800000'; // (red)
$color[2]   = '#cc0000'; // (light red)      Warning/Error Messages
$color[3]   = '#a0b8c8'; // (green-blue)     Left Bar Background
$color[4]   = '#ffffff'; // (white)          Normal Background
$color[5]   = '#ffffcc'; // (light yellow)   Table Headers
$color[6]   = '#000000'; // (black)          Text on left bar
$color[7]   = '#0000cc'; // (blue)           Links
$color[8]   = '#000000'; // (black)          Normal text
$color[9]   = '#ababab'; // (mid-gray)       Darker version of #0
$color[10]  = '#666666'; // (dark gray)      Darker version of #9
$color[11]  = '#770000'; // (dark red)       Special Folders color
$color[12]  = '#ededed'; // (light gray)     Alternate color for message list
$color[13]  = '#800000'; // (dark red)       Color for quoted text -- > 1 quote
$color[14]  = '#ff0000'; // (red)            Color for quoted text -- >> 2 or more
$color[15]  = '#002266'; // (dark blue)      Unselectable folders
$color[16]  = '#ff9933'; // (orange)         Highlight color

/**
 * set color constants in order to use simple names instead of color array
 * 0 - SQM_TEXT_DISABLED, SQM_TITLE_BACKGROUND, SQM_BUTTON_BACKGROUND_DISABLED,
 *     SQM_ROW_BACKGROUND_1
 * 1 -
 * 2 - SQM_ERROR_TEXT
 * 3 - SQM_BACKGROUND_LEFT
 * 4 - SQM_BACKGROUND
 * 5 - SQM_ROW_BACKGROUND_HIGHLIGHT, SQM_COLUMN_HEADER_BACKGROUND
 * 6 - SQM_TEXT_STANDARD_LEFT
 * 7 - SQM_TITLE_TEXT, SQM_BLOCK_TITLE_TEXT
 * 8 - SQM_TEXT_STANDARD, SQM_BUTTON_TEXT, SQM_BLOCK_TEXT, SQM_ROW_TEXT_1,
 *     SQM_ROW_TEXT_2, SQM_ROW_TEXT_HIGHLIGHT, SQM_ROW_TEXT_SELECTED,
 *     SQM_COLUMN_HEADER_TEXT
 * 9 - SQM_BUTTON_BACKGROUND
 * 10 - SQM_BLOCK_TITLE
 * 11 - SQM_TEXT_SPECIAL
 * 12 - SQM_BUTTON_BACKGROUND_TEXT, SQM_BLOCK_BACKGROUND, SQM_ROW_BACKGROUND_2
 * 13 - SQM_MESSAGE_QUOTE_1
 * 14 - SQM_MESSAGE_QUOTE_2
 * 15 - SQM_TEXT_HIGHLIGHT
 * 16 - SQM_ROW_BACKGROUND_SELECTED
 */
define('SQM_BACKGROUND',$color[4]);
define('SQM_BACKGROUND_LEFT',$color[3]);

define('SQM_TEXT_STANDARD',$color[8]);
define('SQM_TEXT_STANDARD_LEFT',$color[6]);
define('SQM_TEXT_HIGHLIGHT',$color[15]);
define('SQM_TEXT_DISABLED',$color[0]);
define('SQM_TEXT_SPECIAL',$color[11]);

define('SQM_LINK',$color[7]);
define('SQM_LINK_LEFT',$color[6]);

define('SQM_TITLE_BACKGROUND',$color[0]);
define('SQM_TITLE_TEXT',$color[7]);

define('SQM_BUTTON_BACKGROUND',$color[9]);
define('SQM_BUTTON_TEXT',$color[8]);
define('SQM_BUTTON_BACKGROUND_DISABLED',$color[0]);
define('SQM_BUTTON_BACKGROUND_TEXT',$color[12]);

define('SQM_BLOCK_BACKGROUND',$color[12]);
define('SQM_BLOCK_TEXT',$color[8]);
define('SQM_BLOCK_TITLE',$color[10]);
define('SQM_BLOCK_TITLE_TEXT',$color[7]);

define('SQM_ROW_BACKGROUND_1',$color[0]);
define('SQM_ROW_BACKGROUND_2',$color[12]);
define('SQM_ROW_TEXT_1',$color[8]);
define('SQM_ROW_TEXT_2',$color[8]);
define('SQM_ROW_BACKGROUND_HIGHLIGHT',$color[5]);
define('SQM_ROW_TEXT_HIGHLIGHT',$color[8]);
define('SQM_ROW_BACKGROUND_SELECTED',$color[16]);
define('SQM_ROW_TEXT_SELECTED',$color[8]);

define('SQM_COLUMN_HEADER_BACKGROUND',$color[5]);
define('SQM_COLUMN_HEADER_TEXT',$color[8]);

define('SQM_MESSAGE_QUOTE_1',$color[13]);
define('SQM_MESSAGE_QUOTE_2',$color[14]);

define('SQM_ERROR_TEXT',$color[2]);

define('SQM_ALIGN_LEFT', $align['left']);
define('SQM_ALIGN_RIGHT', $align['right']);

// END TODO

if (sqgetGlobalVar('fontset',$fontset,SQ_GET) &&
    isset($fontsets[$fontset])) {
    $fontfamily=$fontsets[$fontset];
} else {
    $fontfamily='';
}

if (! sqgetGlobalVar('fontsize',$fontsize,SQ_GET)) {
    $fontsize = 0;
} else {
    $fontsize = (int) $fontsize;
}

$oTemplate->header('Content-Type: text/css');
/**
 * GOTCHA #1: When sending the headers for caching, we must send Expires,
 *            Last-Modified, Pragma, and Cache-Control headers.  If we don't PHP 
 *            will makeup values that will break the cacheing.
 * 
 * GOTCHA #2: If the current template does not contain a template named
 *            stylesheet.tpl, this cacheing will break because filemtime() won't
 *            work.  This is a problem e.g. with the default_advanced template
 *            that inherits CSS properties from the default template but
 *            doesn't contain stylesheet.tpl itself.
IDEA: So ask the Template class object to return the mtime or better yet, the full file path (at least from SM_PATH) by using $oTemplate->get_template_file_path(stylesheet.tpl) but this is still a problem if the default template also does not have such a file (in which case, we fall back to SM's css/deafult css file (so in that case, go get that file's mtime!)
 *            Possibly naive suggestion - template can define its own default 
 *            template name
 * 
 * GOTCHA #3: If the user changes user prefs for things like font size then
 *            the mtime should be updated to the time of that change, and not
 *            that of the stylesheet.tpl file.  IDEA: can this be a value kept
 *            in user prefs (always compare to actual file mtime before sending
 *            to the browser)
 *
 * Comment re gotcha #3: If we only define basic font prefs here, we really
 * only need to refresh the cache if one of the font prefs changes.
 * Possibly some type of "force nocache flag could be set if a font pref is
 * changed?
 *
 * Response: Perhaps, if that can be implemented great, but I think the 
 * user prefs solution would definitely work: catch the font setting change
 * in a custom save() routine from user prefs and away we go. 
 *
 * TODO: Fix this. :)
 */

if ( $lastmod = @filemtime(SM_PATH . $oTemplate->get_template_file_directory() 
                         . 'css/stylesheet.tpl') ) {
    $gmlastmod = gmdate('D, d M Y H:i:s', $lastmod) . ' GMT';
    $expires = gmdate('D, d M Y H:i:s', strtotime('+1 week')) . ' GMT';
    $oTemplate->header('Last-Modified: ' . $gmlastmod);
    $oTemplate->header('Expires: '. $expires);
    $oTemplate->header('Pragma: ');
    $oTemplate->header('Cache-Control: public, must-revalidate');
}
// Steve, why did you remove this?  Is it irrelevant now?  If so, let's 
// remove the rest of the references to it here (note that it is being
// used above in the filetime() statement) and elsewhere
// $oTemplate->display('css/stylesheet.tpl');

// don't show version as a security measure
//$oTemplate->header('X-Powered-By: SquirrelMail/' . SM_VERSION, FALSE);
$oTemplate->header('X-Powered-By: SquirrelMail', FALSE);

/**
 * Additional styles are now handled by adding stylesheets to 
 * templates/<template>/css/, so here, we simply define some 
 * basic styles based on user prefs.
 */
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

