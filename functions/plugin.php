<?php

/**
 * plugin.php
 *
 * Copyright (c) 1999-2001 The SquirrelMail Development Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file provides the framework for a plugin architecture.
 *
 * Documentation on how to write plugins might show up some time.
 *
 * $Id$
 */

/*****************************************************************/
/*** THIS FILE NEEDS TO HAVE ITS FORMATTING FIXED!!!           ***/
/*** PLEASE DO SO AND REMOVE THIS COMMENT SECTION.             ***/
/***    + Base level indent should begin at left margin, as    ***/
/***      the first line of the function definition below.     ***/
/***    + All identation should consist of four space blocks   ***/
/***    + Tab characters are evil.                             ***/
/***    + all comments should use "slash-star ... star-slash"  ***/
/***      style -- no pound characters, no slash-slash style   ***/
/***    + FLOW CONTROL STATEMENTS (if, while, etc) SHOULD      ***/
/***      ALWAYS USE { AND } CHARACTERS!!!                     ***/
/***    + Please use ' instead of ", when possible. Note "     ***/
/***      should always be used in _( ) function calls.        ***/
/*** Thank you for your help making the SM code more readable. ***/
/*****************************************************************/

global $squirrelmail_plugin_hooks;
$squirrelmail_plugin_hooks = array();

    // This function adds a plugin
    function use_plugin ($name) {

        if (file_exists('../plugins/'.$name.'/setup.php')) {
            include_once('../plugins/'.$name.'/setup.php');
            $function = 'squirrelmail_plugin_init_'.$name;
            if (function_exists($function)) {
                $function();
            }
        }

    }

    // This function executes a hook
    function do_hook ($name) {
        global $squirrelmail_plugin_hooks;
        $Data = func_get_args();
        if (isset($squirrelmail_plugin_hooks[$name]) &&
            is_array($squirrelmail_plugin_hooks[$name])) {
            foreach ($squirrelmail_plugin_hooks[$name] as $function) {
                // Add something to set correct gettext domain for plugin
                if (function_exists($function)) {
                    $function($Data);
                }
            }
        }

        // Variable-length argument lists have a slight problem when
        // passing values by reference.  Pity.  This is a workaround.
        return $Data;
    }

    /* -------------------- MAIN --------------------- */

    // On startup, register all plugins configured for use
    if (isset($plugins) && is_array($plugins)) {
        foreach ($plugins as $name) {
            use_plugin($name);
        }
    }

?>
