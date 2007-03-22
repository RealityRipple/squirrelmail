<?php

/**
 * plugin.php
 *
 * This file provides the framework for a plugin architecture.
 *
 * Documentation on how to write plugins might show up some time.
 *
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
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
 * This function executes a plugin hook.
 *
 * It includes an arbitrary return value that is managed by
 * all plugins on the same hook and returned to the core hook
 * location.
 *
 * The desired format of the return value should be defined 
 * by the context in which the hook is called.
 *
 * Note that the master return value for this hook is passed
 * to each plugin after the main argument(s) value/array as a 
 * convenience only - to show what the current return value is
 * even though it is liable to be changed by other plugins.
 *
 * If any plugin on this hook wants to modify the $args
 * plugin parameter, it simply has to use call-by-reference
 * syntax in the hook function that it has registered for the
 * current hook.  Note that this is in addition to (entirely
 * independent of) the return value for this hook.
 *
 * @param string $name Name of hook being executed
 * @param mixed  $args A single value or an array of arguments 
 *                     that are to be passed to all plugins 
 *                     operating off the hook being called.  
 *                     Note that this argument is passed by 
 *                     reference thus it is liable to be 
 *                     changed after the hook completes.
 *
 * @return mixed The return value that is managed by the plugins
 *               on the current hook.
 *
 */
function do_hook($name, &$args) {

    global $squirrelmail_plugin_hooks, $currentHookName;
    $currentHookName = $name;
    $ret = NULL;

    if (isset($squirrelmail_plugin_hooks[$name])
          && is_array($squirrelmail_plugin_hooks[$name])) {
        foreach ($squirrelmail_plugin_hooks[$name] as $plugin_name => $function) {
            use_plugin($plugin_name);
            if (function_exists($function)) {
                $ret = $function($args, $ret);

                // each plugin can call additional hooks, so need
                // to make sure the current hook name is accurate
                // again after each plugin has finished
                //
                $currentHookName = $name;
            }
        }
    }

    $currentHookName = '';
    return $ret;

}

/**
 * This function executes a hook that allows for an arbitrary
 * return value from each plugin that will be merged into one
 * array (or one string if all return values are strings) and
 * returned to the core hook location.
 *
 * Note that unlike PHP's array_merge function, matching array keys
 * will not overwrite each other, instead, values under such keys
 * will be concatenated if they are both strings, or merged if they
 * are arrays (in the same (non-overwrite) manner recursively).
 *
 * Plugins returning non-arrays (strings, objects, etc) will have 
 * their output added to the end of the ultimate return array, 
 * unless ALL values returned are strings, in which case one string
 * with all returned strings concatenated together is returned 
 * (unless $force_array is TRUE).
 *
 * If any plugin on this hook wants to modify the $args
 * plugin parameter, it simply has to use call-by-reference
 * syntax in the hook function that it has registered for the
 * current hook.  Note that this is in addition to (entirely
 * independent of) the return value for this hook.
 *
 * @param string  $name Name of hook being executed
 * @param mixed   $args A single value or an array of arguments 
 *                      that are to be passed to all plugins 
 *                      operating off the hook being called.  
 *                      Note that this argument is passed by 
 *                      reference thus it is liable to be 
 *                      changed after the hook completes.
 * @param boolean $force_array When TRUE, guarantees the return
 *                             value will ALWAYS be an array,
 *                             (simple strings will be forced
 *                             into a one-element array). 
 *                             When FALSE, behavior is as 
 *                             described above (OPTIONAL;
 *                             default behavior is to return
 *                             mixed - array or string).
 *
 * @return mixed the merged return arrays or strings of each
 *               plugin on this hook.
 *
 */
function concat_hook_function($name, &$args, $force_array=FALSE) {

    global $squirrelmail_plugin_hooks, $currentHookName;
    $currentHookName = $name;
    $ret = '';

    if (isset($squirrelmail_plugin_hooks[$name])
          && is_array($squirrelmail_plugin_hooks[$name])) {
        foreach ($squirrelmail_plugin_hooks[$name] as $plugin_name => $function) {
            use_plugin($plugin_name);
            if (function_exists($function)) {
                $plugin_ret = $function($args);
                if (!empty($plugin_ret)) {
                    $ret = sqm_array_merge($ret, $plugin_ret);
                }

                // each plugin can call additional hooks, so need
                // to make sure the current hook name is accurate
                // again after each plugin has finished
                //
                $currentHookName = $name;
            }
        }
    }

    if ($force_array && is_string($ret)) {
        $ret = array($ret);
    }

    $currentHookName = '';
    return $ret;

}

/**
 * This function is used for hooks which are to return true or
 * false. If $priority is > 0, any one or more trues will override
 * any falses. If $priority < 0, then one or more falses will
 * override any trues.
 * Priority 0 means majority rules.  Ties will be broken with $tie
 *
 * If any plugin on this hook wants to modify the $args
 * plugin parameter, it simply has to use call-by-reference
 * syntax in the hook function that it has registered for the
 * current hook.  Note that this is in addition to (entirely
 * independent of) the return value for this hook.
 *
 * @param string  $name     The hook name
 * @param mixed   $args     A single value or an array of arguments 
 *                          that are to be passed to all plugins 
 *                          operating off the hook being called.  
 *                          Note that this argument is passed by 
 *                          reference thus it is liable to be 
 *                          changed after the hook completes.
 * @param int     $priority See explanation above
 * @param boolean $tie      See explanation above
 *
 * @return boolean The result of the function
 *
 */
function boolean_hook_function($name, &$args, $priority=0, $tie=false) {

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
                $ret = $function($args);
                if ($ret) {
                    $yea++;
                } else {
                    $nay++;
                }

                // each plugin can call additional hooks, so need
                // to make sure the current hook name is accurate
                // again after each plugin has finished
                //
                $currentHookName = $name;
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
 * Do not use, use checkForJavascript() instead.
 *
 * This function checks whether the user's USER_AGENT is known to
 * be broken. If so, returns true and the plugin is invisible to the
 * offending browser.
 * *** THIS IS A TEST FOR JAVASCRIPT SUPPORT ***
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
  * Get a plugin's version.
  *
  * Determines and returns a plugin's version.
  *
  * By default, the desired plugin must be currently
  * activated, and if it is not, this function will
  * return FALSE.  By overriding the default value
  * of $force_inclusion, this function will attempt
  * to grab versioning information from the given
  * plugin even if it is not activated (plugin still
  * has to be unpackaged and set in place in the
  * plugins directory).  Use with care - some plugins
  * might break SquirrelMail when this is used.
  *
  * By turning on the $do_parse argument, the version
  * string will be parsed by SquirrelMail into a
  * SquirrelMail-compatible version string (such as
  * "1.2.3") if it is not already.
  *
  * Note that this assumes plugin versioning is
  * consistently applied in the same fashion that
  * SquirrelMail versions are, with the exception that
  * an applicable SquirrelMail version may be appended
  * to the version number (which will be ignored herein).
  * That is, plugin version number schemes are expected
  * in the following format:  1.2.3, or 1.2.3-1.4.0.
  *
  * Any characters after the third version number
  * indicating things such as beta or release candidate
  * versions are discarded, so formats such as the
  * following will also work, although extra information
  * about beta versions can possibly confuse the desired
  * results of the version check:  1.2.3-beta4, 1.2.3.RC2,
  * and so forth.
  *
  * @since 1.5.2
  *
  * @param string plugin_name   name of the plugin to
  *                             check; must precisely
  *                             match the plugin
  *                             directory name
  * @param bool force_inclusion try to get version info
  *                             for plugins not activated?
  *                             (default FALSE)
  * @param bool do_parse        return the plugin version
  *                             in SquirrelMail-compatible
  *                             format (default FALSE)
  *
  * @return mixed The plugin version string if found, otherwise,
  *               boolean FALSE is returned indicating that no
  *               version information could be found for the plugin.
  *
  */
function get_plugin_version($plugin_name, $force_inclusion = FALSE, $do_parse = FALSE)
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


   if ($plugin_version && $do_parse)
   {

      // massage version number into something we understand
      //
      // the first regexp strips everything and anything that follows
      // the first occurance of a non-digit (or non decimal point), so
      // beware that putting letters in the middle of a version string
      // will effectively truncate the version string right there (but
      // this also just helps remove the SquirrelMail version part off
      // of versions such as "1.2.3-1.4.4")
      //
      // the second regexp just strips out non-digits/non-decimal points
      // (and might be redundant(?))
      //
      // the regexps are wrapped in a trim that makes sure the version
      // does not start or end with a decimal point
      //
      $plugin_version = trim(preg_replace(array('/[^0-9.]+.*$/', '/[^0-9.]/'),
                                          '', $plugin_version),
                             '.');

   }

   return $plugin_version;

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
  * is not activated (the plugin still has to be 
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
  * Any characters after the third number indicating 
  * things such as beta or release candidate versions
  * are discarded, so formats such as the following 
  * will also work, although extra information about 
  * beta versions can possibly confuse the desired results 
  * of the version check:  1.2.3-beta4, 1.2.3.RC2, and so forth.
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

   $plugin_version = get_plugin_version($plugin_name, $force_inclusion, TRUE);
   if (!$plugin_version) return FALSE;


   // split the version string into sections delimited by 
   // decimal points, and make sure we have three sections
   //
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

/**
  * Get a plugin's other plugin dependencies.
  *
  * Determines and returns all the other plugins
  * that a given plugin requires, as well as the
  * minimum version numbers of the required plugins.
  *
  * By default, the desired plugin must be currently 
  * activated, and if it is not, this function will 
  * return FALSE.  By overriding the default value 
  * of $force_inclusion, this function will attempt 
  * to grab dependency information from the given 
  * plugin even if it is not activated (plugin still 
  * has to be unpackaged and set in place in the 
  * plugins directory).  Use with care - some plugins
  * might break SquirrelMail when this is used.
  * 
  * By turning on the $do_parse argument (it is on by
  * default), the version string for each required 
  * plugin will be parsed by SquirrelMail into a 
  * SquirrelMail-compatible version string (such as 
  * "1.2.3") if it is not already.  See notes about 
  * version formatting under get_plugin_version().
  *
  * @since 1.5.2
  *
  * @param string plugin_name   name of the plugin to
  *                             check; must precisely
  *                             match the plugin
  *                             directory name
  * @param bool force_inclusion try to get version info
  *                             for plugins not activated?
  *                             (default FALSE)
  * @param bool do_parse        return the version numbers
  *                             for required plugins in
  *                             SquirrelMail-compatible
  *                             format (default FALSE)
  *
  * @return mixed Boolean FALSE is returned if the plugin
  *               could not be found or does not indicate
  *               whether it has other plugin dependencies, 
  *               otherwise an array is returned where keys 
  *               are the names of required plugin dependencies,
  *               and values are the minimum version required 
  *               for that plugin.  Note that the array might 
  *               be empty, indicating that the plugin has no 
  *               dependencies.
  *
  */
function get_plugin_dependencies($plugin_name, $force_inclusion = FALSE, $do_parse = TRUE)
{

   $info_function = $plugin_name . '_info';
   $plugin_info = array();
   $plugin_dependencies = FALSE;


   // first attempt to find the plugin info function, wherein
   // the plugin dependencies should be available
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
   if (!empty($plugin_info['required_plugins']))
      $plugin_dependencies = $plugin_info['required_plugins'];


   if (!empty($plugin_dependencies) && $do_parse)
   {

      $new_plugin_dependencies = '';
      foreach ($plugin_dependencies as $plugin_name => $plugin_version)
      {

         // massage version number into something we understand
         //
         // the first regexp strips everything and anything that follows
         // the first occurance of a non-digit (or non decimal point), so
         // beware that putting letters in the middle of a version string
         // will effectively truncate the version string right there (but
         // this also just helps remove the SquirrelMail version part off
         // of versions such as "1.2.3-1.4.4")
         //
         // the second regexp just strips out non-digits/non-decimal points
         // (and might be redundant(?))
         //
         // the regexps are wrapped in a trim that makes sure the version
         // does not start or end with a decimal point
         //
         $new_plugin_dependencies[$plugin_name] 
            = trim(preg_replace(array('/[^0-9.]+.*$/', '/[^0-9.]/'), 
                                '', $plugin_version), 
                                '.');

      }

      $plugin_dependencies = $new_plugin_dependencies;

   }

   return $plugin_dependencies;

}

/**
  * Check a plugin's other plugin dependencies.
  *
  * Determines whether or not all of the given
  * plugin's required plugins are installed and
  * up to the proper version.
  *
  * By default, the desired plugin must be currently 
  * activated, and if it is not, this function will 
  * return FALSE.  By overriding the default value 
  * of $force_inclusion, this function will attempt 
  * to grab dependency information from the given 
  * plugin even if it is not activated (plugin still 
  * has to be unpackaged and set in place in the 
  * plugins directory).  Use with care - some plugins
  * might break SquirrelMail when this is used.
  * 
  * NOTE that if a plugin does not report whether or
  * not it has other plugin dependencies, this function
  * will return TRUE, although that is possibly incorrect
  * or misleading.
  *
//FIXME:
  * NOTE that currently, the dependencies are checked
  * in such as way as they do not have to be activated;
  * this is due to the large number of plugins that
  * require the Compatibility plugin, which does (should)
  * not need to be activated.  Best solution would be to
  * expand the plugin info function so that it indicates
  * also whether or not the required plugin must be 
  * activated or not.
//FIXME: proposed info() change:
     'required_plugins' => array(
                                 'activated' => array(
                                                      'blah' => '1.0',
                                                     ),
                                 'inactive'  => array(
                                                      'compatibility' => '2.0.5',
                                                     ),
                                )
//FIXME: optional proposed info() change: (I vote for this, the problem, tho, is that it is a bit of a departure from what is already out there...)
     'required_plugins' => array(
                                 'compatibility' => array(
                                                          'version' => '2.0.5',
                                                          'activate' => FALSE,
                                                         )
                                )
//FIXME: optional proposed info() change:
     'required_plugins' => array(
                                 'compatibility::NO_NEED_TO_ACTIVATE::' => '2.0.5',
                                )
//FIXME: optional proposed info() change:
     'required_plugins' => array(
                                 'compatibility' => '2.0.5::NO_NEED_TO_ACTIVATE::',
                                )
  * 
  * @since 1.5.2
  *
  * @param string plugin_name   name of the plugin to
  *                             check; must precisely
  *                             match the plugin
  *                             directory name
  * @param bool force_inclusion try to get version info
  *                             for plugins not activated?
  *                             (default FALSE)
  *
  * @return mixed Boolean TRUE if all of the plugin's 
  *               required plugins are correctly installed,
  *               otherwise an array of the required plugins
  *               that are either not installed or not up to
  *               the minimum required version.  The array is
  *               keyed by plugin name where values are the
  *               (printable, non-parsed) versions required.
  *
  */
function check_plugin_dependencies($plugin_name, $force_inclusion = FALSE)
{

   $dependencies = get_plugin_dependencies($plugin_name, $force_inclusion);
   if (!$dependencies) return TRUE;
   $missing_or_bad = array();

   foreach ($dependencies as $depend_name => $depend_version)
   {
      $version = preg_split('/\./', $depend_version, 3);
      $version[2] = intval($version[2]);
//FIXME: should fix plugin info function API to tell us what the value of $force_dependency_inclusion should be below
      //$force_dependency_inclusion = !$the_plugin_info_thingy_array['required_plugins']['must_be_activated'];
      $force_dependency_inclusion = TRUE;
      if (!check_plugin_version($depend_name, $version[0], $version[1], $version[2], $force_dependency_inclusion))
         $missing_or_bad[$depend_name] = $depend_version;
   }

   if (empty($missing_or_bad)) return TRUE;


   // get non-parsed required versions
   //
   $non_parsed_dependencies = get_plugin_dependencies($plugin_name, $force_inclusion, FALSE);
   $return_array = array();
   foreach ($missing_or_bad as $depend_name => $ignore)
      $return_array[$depend_name] = $non_parsed_dependencies[$depend_name];

   return $return_array;

}

