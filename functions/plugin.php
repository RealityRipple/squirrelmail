<?php

/**
 * plugin.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file provides the framework for a plugin architecture.
 *
 * Documentation on how to write plugins might show up some time.
 *
 * $Id$
 */

global $squirrelmail_plugin_hooks;
$squirrelmail_plugin_hooks = array();

/* This function adds a plugin. */
function use_plugin ($name) {
    if (file_exists("../plugins/$name/setup.php")) {
        include_once("../plugins/$name/setup.php");
        $function = "squirrelmail_plugin_init_$name";
        if (function_exists($function)) {
            $function();
        }
    }
}

/* This function executes a hook. */
function do_hook ($name) {
    global $squirrelmail_plugin_hooks;
    $data = func_get_args();

    if (isset($squirrelmail_plugin_hooks[$name])
          && is_array($squirrelmail_plugin_hooks[$name])) {
        foreach ($squirrelmail_plugin_hooks[$name] as $function) {
            /* Add something to set correct gettext domain for plugin. */
            if (function_exists($function)) {
                $function($data);
            }
        }
    }

    /* Variable-length argument lists have a slight problem when */
    /* passing values by reference. Pity. This is a workaround.  */
    return $data;
}

/*************************************/
/*** MAIN PLUGIN LOADING CODE HERE ***/
/*************************************/

/* On startup, register all plugins configured for use. */
if (isset($plugins) && is_array($plugins)) {
    foreach ($plugins as $name) {
        use_plugin($name);
    }
}

?>
