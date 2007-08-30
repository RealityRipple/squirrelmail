<?php

/**
 * SquirrelMail Test Plugin
 * @copyright &copy; 2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage test
 */


include_once('../../include/init.php');

global $oTemplate, $color;

displayPageHeader($color, 'none');

$oTemplate->display('plugins/test/test_menu.tpl');
$oTemplate->display('footer.tpl');

