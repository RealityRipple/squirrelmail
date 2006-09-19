<?php
/**
 * stylelink.tpl
 *
 * Template for rendering the links to css sheets for the page
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/** add required includes */
require_once(SM_PATH.'templates/util_css.php');

/** extract variables */
extract($t);

//echo SM_PATH;
$base_css="css";

$template_css_file="templates/$templatedir/$templatedir.css";

/** Add URLs to the $css_files array to have them added as links before any theme css or style.php output **/
$css_files=array();

/** If in existance, add link to template css file named for template **/
if (is_file(SM_PATH.$template_css_file)) $css_files[]=$base_uri.$template_css_file;

$base_css_files=list_css_files(SM_PATH.$base_css,$base_uri.$base_css);

if (!$base_css_files) $base_css_files=array();

/** Add link to default.css in the css directory **/
$css_link_html='';
$css_url=$base_uri.$base_css."/default.css";
$css_link_html=css_link($css_url, null, false, 'all','');

/** Add links to all css files listed in the css_files collection **/
foreach ($css_files as $css_url) {
  $css_link_html.=css_link($css_url, null, false, 'screen', '');
}

/** Add links to each of the .css files in the /css/ directory, making them as alternate if they are not named for the current theme **/
foreach ($base_css_files as $css_file=>$css_url) {
  $css_file_theme=substr($css_file,0,-4);
//    echo $css_file_theme;
   $css_link_html.=css_link($css_url, $css_file_theme, ($css_file_theme!=$themeid), 'screen', '');
}

/** output CSS links **/
echo $css_link_html;

