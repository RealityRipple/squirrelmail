<?php

/**
  * SquirrelMail Test Plugin
  * @copyright &copy; 2006 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id: setup.php 11080 2006-04-23 19:00:45Z tokul $
  * @package plugins
  * @subpackage test
  */

/**
  * Add link to menu at top of content pane
  *
  * @return void
  *
  */
function test_menuline_do() {

    global $oTemplate;
    $nbsp = $oTemplate->fetch('non_breaking_space.tpl');
    $output = makeInternalLink('plugins/test/test.php', 'Test', 'right')
            . $nbsp . $nbsp;
    return array('menuline' => $output);

}


