<?php

/**
 * Provides some basic configuration options to the template engine
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */


/**
 * Indicates what template engine this template set uses.
 */
$template_engine = SQ_PHP_TEMPLATE;


/**
 * Indicates what the content type is for this template set.
 */
//TODO - what is the proper content-type? (most recommend text/xml for SOAP messages, but some such as w3schools.com recommend application/soap+xml)
//$content_type = 'application/soap+xml';
$content_type = 'text/xml';


/**
  * If non-empty, indicates which template set this set is derived from.
  *
  * If a template file does not exist in this template set, then the
  * parent set is searched for the file.  If not found there and that
  * set has a parent itself (the grandparent of this set), the file is
  * searched for there....  This continues until there are no more parent
  * template sets, and if the file is still not found, the fall-back
  * template set (see $templateset_fallback in config/config.php) is the 
  * last placed searched for the file.
  *
  */
$parent_template_set = '';


/**
  * These settings allow this template set to change SquirrelMail's
  * list of active plugins by adding or removing any of those listed
  * herein.  If the $remove_plugins list contains "*", then ALL plugins
  * will be disabled, and only those in $add_plugins will be enabled.
  *
  */
$add_plugins = array();
$remove_plugins = array();


