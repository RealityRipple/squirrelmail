<?php

/**
 * Provides some basic configuration options to the template engine
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
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
 * Required Javascript files for this template set.  If a JS file is listed
 * here, but not listed in the provided js files below, SquirrelMail will use
 * the file by the same name in the default template directory.
 */
$required_js_files = array  (
                                'default.js',
                            );

/**
 * Alternate stylesheets provided by this template.  Format detailed below.
 **/
$alternate_stylesheets = array (
                                    # CSS File         Stlye Name
#                                   'example.css'   => 'My Example Style',
                               );


