<?php

/**
  * SquirrelMail Preview Pane Plugin
  *
  * @copyright &copy; 1999-2007 The SquirrelMail Project Team
  * @author Paul Lesneiwski <paul@squirrelmail.org>
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package plugins
  * @subpackage preview_pane
  *
  */


include_once('../../include/init.php');

global $org_title;
displayHtmlHeader($org_title, '', FALSE, FALSE);

$oTemplate->display('plugins/preview_pane/empty_frame.tpl');
$oTemplate->display('footer.tpl');


