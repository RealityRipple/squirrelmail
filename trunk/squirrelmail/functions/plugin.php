<?php

/**
 * plugin.php
 *
 * This file provides the framework for a plugin architecture.
 *
 * Documentation on how to write plugins might show up some time.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/**
 * This function adds a plugin.
 * @param string $name Internal plugin name (ie. delete_move_next)
 * @return void
 */
function use_plugin ($name) {
    if (file_exists(SM_PATH . "plugins/$name/setup.php")) {
        include_once(SM_PATH . "plugins/$name/setup.php");

        /**
          * As of SM 1.5.2, plugin hook registration is statically
          * accomplished using the configuration utility (config/conf.pl).
          * And this code is deprecated (but let's keep it until 
          * the new registration system is proven).
          *
          */
        //$function = "squirrelmail_plugin_init_$name";
        //if (function_exists($function)) {
        //    $function();
        //}
    }
}

/**
 * This function executes a hook.
 * @param string $name Name of hook to fire
 * @return mixed $data
 */
function do_hook ($name) {
    global $squirrelmail_plugin_hooks, $currentHookName;
    $data = func_get_args();
    $currentHookName = $name;

    if (isset($squirrelmail_plugin_hooks[$name])
          && is_array($squirrelmail_plugin_hooks[$name])) {
        foreach ($squirrelmail_plugin_hooks[$name] as $plugin_name => $function) {
            use_plugin($plugin_name);
            /* Add something to set correct gettext domain for plugin. */
            if (function_exists($function)) {
                $function($data);
            }
        }
    }

    $currentHookName = '';

    /* Variable-length argument lists have a slight problem when */
    /* passing values by reference. Pity. This is a workaround.  */
    return $data;
}

/**
 * This function executes a hook and allows for parameters to be 
 * passed, wherein each plugin can modify the parameters before 
 * they are passed to the next funciton. Whether or not the 
 * parameters are modified, plugins on this hook should always
 * return the given parameters.
 *
 * @param string name the name of the hook
 * @param mixed param the parameters to pass to the hook function
 * @return mixed the possibly modified hook parameters
 */
function filter_hook_function($name,$parm=NULL) {
    global $squirrelmail_plugin_hooks, $currentHookName;
    $currentHookName = $name;

    if (isset($squirrelmail_plugin_hooks[$name])
          && is_array($squirrelmail_plugin_hooks[$name])) {
        foreach ($squirrelmail_plugin_hooks[$name] as $plugin_name => $function) {
            use_plugin($plugin_name);
            /* Add something to set correct gettext domain for plugin. */
            if (function_exists($function)) {
                $parm = $function($parm);
            }
        }
    }

    $currentHookName = '';

    /* Variable-length argument lists have a slight problem when */
    /* passing values by reference. Pity. This is a workaround.  */
    return $parm;
}

/**
 * This function executes a hook and allows for parameters to be passed.
 *
 * @param string name the name of the hook
 * @param mixed param the parameters to pass to the hook function
 * @return mixed the return value of the hook function
 */
function do_hook_function($name,$parm=NULL) {
    global $squirrelmail_plugin_hooks, $currentHookName;
    $ret = '';
    $currentHookName = $name;

    if (isset($squirrelmail_plugin_hooks[$name])
          && is_array($squirrelmail_plugin_hooks[$name])) {
        foreach ($squirrelmail_plugin_hooks[$name] as $plugin_name => $function) {
            use_plugin($plugin_name);
            /* Add something to set correct gettext domain for plugin. */
            if (function_exists($function)) {
                $ret = $function($parm);
            }
        }
    }

    $currentHookName = '';

    /* Variable-length argument lists have a slight problem when */
    /* passing values by reference. Pity. This is a workaround.  */
    return $ret;
}

/**
 * This function executes a hook, allows for parameters to be passed,
 * and looks for an array returned from each plugin: each array is 
 * then merged into one and returned to the core hook location.
 *
 * Note that unlike PHP's array_merge function, matching array keys
 * will not overwrite each other, instead, values under such keys
 * will be concatenated if they are both strings, or merged if they
 * are arrays (in the same (non-overwrite) manner recursively).
 *
 * Plugins returning non-arrays (strings, objects, etc) will have 
 * their output added to the end of the ultimate return array, 
 * unless ALL values returned are strings, in which case one string
 * with all returned strings concatenated together is returned.
 *
 * @param string name the name of the hook
 * @param mixed param the parameters to pass to the hook function
 *
 * @return mixed the merged return arrays or strings of each
 *               plugin on this hook
 *
 */
function concat_hook_function($name,$parm=NULL) {
    global $squirrelmail_plugin_hooks, $currentHookName;
    $ret = '';
    $currentHookName = $name;

    if (isset($squirrelmail_plugin_hooks[$name])
          && is_array($squirrelmail_plugin_hooks[$name])) {
        foreach ($squirrelmail_plugin_hooks[$name] as $plugin_name => $function) {
            use_plugin($plugin_name);
            /* Add something to set correct gettext domain for plugin. */
            if (function_exists($function)) {
                $plugin_ret = $function($parm);
                if (!empty($plugin_ret)) {
                    $ret = sqm_array_merge($ret, $plugin_ret);
                }
            }
        }
    }

    $currentHookName = '';

    /* Variable-length argument lists have a slight problem when */
    /* passing values by reference. Pity. This is a workaround.  */
    return $ret;
}

/**
 * This function is used for hooks which are to return true or
 * false. If $priority is > 0, any one or more trues will override
 * any falses. If $priority < 0, then one or more falses will
 * override any trues.
 * Priority 0 means majority rules.  Ties will be broken with $tie
 *
 * @param string name the hook name
 * @param mixed parm the parameters for the hook function
 * @param int priority
 * @param bool tie
 * @return bool the result of the function
 */
function boolean_hook_function($name,$parm=NULL,$priority=0,$tie=false) {
    global $squirrelmail_plugin_hooks, $currentHookName;
    $yea = 0;
    $nay = 0;
    $ret = $tie;

    if (isset($squirrelmail_plugin_hooks[$name]) &&
        is_array($squirrelmail_plugin_hooks[$name])) {

        /* Loop over the plugins that registered the hook */
        $currentHookName = $name;
        foreach ($squirrelmail_plugin_hooks[$name] as $plugin_name => $function) {
            use_plugin($plugin_name);
            if (function_exists($function)) {
                $ret = $function($parm);
                if ($ret) {
                    $yea++;
                } else {
                    $nay++;
                }
            }
        }
        $currentHookName = '';

        /* Examine the aftermath and assign the return value appropriately */
        if (($priority > 0) && ($yea)) {
            $ret = true;
        } elseif (($priority < 0) && ($nay)) {
            $ret = false;
        } elseif ($yea > $nay) {
            $ret = true;
        } elseif ($nay > $yea) {
            $ret = false;
        } else {
            // There's a tie, no action needed.
        }
        return $ret;
    }
    // If the code gets here, there was a problem - no hooks, etc.
    return NULL;
}

/**
 * This function checks whether the user's USER_AGENT is known to
 * be broken. If so, returns true and the plugin is invisible to the
 * offending browser.
 * *** THIS IS A TEST FOR JAVASCRIPT SUPPORT ***
 * FIXME: This function needs to have its name changed!
 *
 * @return bool whether this browser properly supports JavaScript
 * @deprecated use checkForJavascript() since 1.5.1
 */
function soupNazi(){
    return !checkForJavascript();
}

/**
 * Check if plugin is enabled
 * @param string $plugin_name plugin name
 * @since 1.5.1
 * @return boolean
 */
function is_plugin_enabled($plugin_name) {
  global $plugins;

  /**
   * check if variable is empty. if var is not set, php empty
   * returns true without error notice.
   *
   * then check if it is an array
   */
  if (empty($plugins) || ! is_array($plugins))
    return false;

  if ( in_array($plugin_name,$plugins) ) {
    return true;
  } else {
    return false;
  }
}

/**
  * Check a plugin's version.
  *
  * Returns TRUE if the given plugin is installed, 
  * activated and is at minimum version $a.$b.$c.
  * If any one of those conditions fails, FALSE
  * will be returned (careful of plugins that are
  * sufficiently versioned but are not activated).
  *
  * By overriding the default value of $force_inclusion,
  * this function will attempt to grab versioning
  * information from the given plugin even if it
  * is not activated (plugin still has to be 
  * unpackaged and set in place in the plugins 
  * directory).  Use with care - some plugins
  * might break SquirrelMail when this is used.
  *
  * Note that this function assumes plugin 
  * versioning is consistently applied in the same 
  * fashion that SquirrelMail versions are, with the 
  * exception that an applicable SquirrelMail 
  * version may be appended to the version number 
  * (which will be ignored herein).  That is, plugin 
  * version number schemes are expected in the following
  * format:  1.2.3, or 1.2.3-1.4.0.  
  *
  * Any characters after the third number are discarded, 
  * so formats such as the following will also work, 
  * although extra information about beta versions can 
  * possibly confuse the desired results of the version 
  * check:  1.2.3-beta4, 1.2.3.RC2, and so forth.
  * 
  * @since 1.5.2
  *
  * @param string plugin_name   name of the plugin to
  *                             check; must precisely
  *                             match the plugin
  *                             directory name
  * @param int  a               major version number
  * @param int  b               minor version number
  * @param int  c               release number
  * @param bool force_inclusion try to get version info
  *                             for plugins not activated?
  *                             (default FALSE)
  *
  * @return bool
  *
  */
function check_plugin_version($plugin_name, 
                              $a = 0, $b = 0, $c = 0, 
                              $force_inclusion = FALSE)
{

   $info_function = $plugin_name . '_info';
   $version_function = $plugin_name . '_version';
   $plugin_info = array();
   $plugin_version = FALSE;


   // first attempt to find the plugin info function, wherein
   // the plugin version should be available
   //
   if (function_exists($info_function))
      $plugin_info = $info_function();
   else if ($force_inclusion 
    && file_exists(SM_PATH . 'plugins/' . $plugin_name . '/setup.php'))
   {
      include_once(SM_PATH . 'plugins/' . $plugin_name . '/setup.php');
      if (function_exists($info_function))
         $plugin_info = $info_function();
   }
   if (!empty($plugin_info['version']))
      $plugin_version = $plugin_info['version'];


   // otherwise, look for older version function 
   //
   if (!$plugin_version && function_exists($version_function))
         $plugin_version = $version_function();


   if (!$plugin_version) return FALSE;


   // now massage version number into something we understand
   //
   $plugin_version = trim(preg_replace(array('/[^0-9.]+.*$/', '/[^0-9.]/'), 
                                       '', $plugin_version), 
                          '.');
   $plugin_version = explode('.', $plugin_version);
   if (!isset($plugin_version[0])) $plugin_version[0] = 0;
   if (!isset($plugin_version[1])) $plugin_version[1] = 0;
   if (!isset($plugin_version[2])) $plugin_version[2] = 0;
//   sm_print_r($plugin_version);


   // now test the version number
   //
   if ($plugin_version[0] < $a ||
      ($plugin_version[0] == $a && $plugin_version[1] < $b) ||
      ($plugin_version[0] == $a && $plugin_version[1] == $b && $plugin_version[2] < $c))
         return FALSE;


   return TRUE;

}

