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
 * Each template provided by this set should be listed in this array.  If a
 * template is requested that is not listed here, the default template will be
 * displayed.  The templates listed below must be in the same directory as
 * this file, e.g. the template root directory.
 */
$templates_provided = array (
                                'left_main.tpl',
                                'stylesheet_advanced.tpl',
                                'options_order.tpl',
                                'options_highlight_list.tpl',
                                'options_highlight_addedit.tpl',
                                'login.tpl'
                            );

/**
 * Required Javascript files for this template set.  If a JS file is listed
 * here, but not listed in the provided js files below, SquirrelMail will use
 * the file by the same name in the default template directory.
 */
$required_js_files = array  (
                                'default.js',
                                'dtree.js'
                            );
                            
/**
 * Any aditional Javascript files that are needed by this template should be
 * listed in this array.  Javascript files must be in a directory called "js/"
 * within the template root directory.
 */
$provided_js_files = array  (
                                'dtree.js'
                            );

/**
 * Additional CSS stylesheets may be provided by template authors.  
 * stylesheet.tpl will always be included in all pages.  Any additional 
 * stylesheets provided by this template should be listed here.  These
 * stylesheets will be automatically included on each page.  They should be
 * located in the template root directory.
 */
$additional_css_sheets = array  (
                                    'stylesheet_advanced.tpl'
                                );
