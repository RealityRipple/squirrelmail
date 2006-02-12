<?php
/**
 * Style sheet script
 *
 * Script processes GET arguments and generates CSS output from stylesheet.tpl.
 * Used GET arguments:
 * <ul>
 *   <li>themeid - string, sets theme file from themes/*.php
 *   <li>templateid - string, sets template directory from templates/
 *   <li>fontset - string, sets selected set of fonts from $fontsets array.
 *   <li>fontsize - integer, sets selected font size
 * </ul>
 * @copyright &copy; 2005-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** @ignore */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'functions/i18n.php');

/* safety check for older config.php */
if (!isset($fontsets) || !is_array($fontsets)) {
    $fontsets=array();
}


/* template init */
/** start block copy from right_main.php */
include_once(SM_PATH . 'class/template/template.class.php');

/* get template name and set used template directory */
if (sqgetGlobalVar('templateid',$templateid,SQ_GET) &&
    file_exists(SM_PATH.'templates/'.basename($templateid).'/stylesheet.tpl')) {
    $sTplDir = SM_PATH.'templates/'.basename($templateid).'/';
} else {
    $sTplDir = SM_PATH.'templates/default/';
}

$oTemplate = new Template($sTplDir);
/** end block copy */

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

/** get theme from GET */
if (sqgetGlobalVar('themeid',$themeid,SQ_GET) &&
    file_exists(SM_PATH . 'themes/'.basename($themeid,'.php').'.php')) {
    include_once(SM_PATH . 'themes/'.basename($themeid,'.php').'.php');
} elseif (file_exists($theme[$theme_default]['PATH'])) {
    include_once($theme[$theme_default]['PATH']);
}

/**
 * TODO: tokul. $languages are not loaded here.
 * get alignment variable from language settings...
 * MOVE THIS to a central init section !!!!
 */
if (!sqgetGlobalVar('align',$align,SQ_SESSION)) {
    $dir = ( isset( $languages[$squirrelmail_language]['DIR']) ) ? $languages[$squirrelmail_language]['DIR'] : 'ltr';
    if ( $dir == 'ltr' ) {
        $align = array('left' => 'left', 'right' => 'right');
    } else {
        $align = array('left' => 'right', 'right' => 'left');
    }
    sqsession_register($align, 'align');
}

/**/
$oTemplate->assign('color', $color);

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

if (sqgetGlobalVar('fontset',$fontset,SQ_GET) &&
    isset($fontsets[$fontset])) {
    $fontfamily=$fontsets[$fontset];
} else {
    $fontfamily='';
}
$oTemplate->assign('fontfamily', $fontfamily);

if (! sqgetGlobalVar('fontsize',$fontsize,SQ_GET)) {
    $fontsize = 0;
} else {
    $fontsize = (int) $fontsize;
}
$oTemplate->assign('fontsize', $fontsize);

header('Content-Type: text/css');
// output a last-modified header if we can
if ( $lastmod = @filemtime($oTemplate->template_dir . 'stylesheet.tpl') ) {
    $gmlastmod = gmdate('D, d M Y H:i:s', $lastmod) . ' GMT';
    header('Last-Modified: ' . $gmlastmod);
}
$oTemplate->display('stylesheet.tpl');

?>
