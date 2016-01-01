<?php

/**
  * SquirrelMail Demo Plugin
  *
  * This page is used as a place holder for custom plugin 
  * pages that are accessed directly by the client.
  *
  * @copyright 2006-2016 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package plugins
  * @subpackage demo
  */


include_once('../../include/init.php');


// Make sure plugin is activated!
//
global $plugins;
if (!in_array('demo', $plugins))
   exit;


global $oTemplate, $color;
displayPageHeader($color, '');

$oTemplate->display('plugins/demo/demo.tpl');
$oTemplate->display('footer.tpl');



