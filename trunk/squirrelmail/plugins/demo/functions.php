<?php


/**
  * SquirrelMail Demo Plugin
  *
  * @copyright &copy; 2006-2007 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package plugins
  * @subpackage demo
  */


/**
  * Add link to menu at top of content pane
  *
  * @return void
  *
  */
function demo_page_header_template_do()
{
   global $oTemplate;

   sq_change_text_domain('demo');
   $nbsp = $oTemplate->fetch('non_breaking_space.tpl');
   $output = makeInternalLink('plugins/demo/demo.php', _("Demo"), '')
           . $nbsp . $nbsp;
   sq_change_text_domain('squirrelmail');

   return array('menuline' => $output);
}



/**
  * Inserts an option block in the main SM options page
  *
  */
function demo_option_link_do()
{

   global $optpage_blocks;

   sq_change_text_domain('demo');

   $optpage_blocks[] = array(
      'name' => _("Demo"),
      'url' => sqm_baseuri() . 'plugins/demo/demo.php',
      'desc' => _("This is where you would describe what your plugin does."),
      'js' => FALSE
   );

   sq_change_text_domain('squirrelmail');

}



