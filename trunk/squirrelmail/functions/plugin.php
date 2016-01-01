<?php

/**
 * plugin.php
 *
 * This file provides the framework for a plugin architecture.
 *
 * Documentation on how to write plugins might show up some time.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
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
 * @param mixed &$args A single value or an array of arguments 
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
 * @param mixed &$args A single value or an array of arguments 
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
 * @param mixed   &$args    A single value or an array of arguments 
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

      /* --- Old code, keeping just in case... problem with it is, for example,
         if it is used, but later we are checking if the same plugin is
         activated (because it SHOULD be), this code having run will possibly 
         create a false positive. 
      include_once(SM_PATH . 'plugins/' . $plugin_name . '/setup.php');
      if (function_exists($info_function))
         $plugin_info = $info_function();
      --- */

      // so what we need to do is process this plugin without
      // it polluting our environment
      //
      // we *could* just use the above code, which is more of a
      // sure thing than some regular expressions, and then test
      // the contents of the $plugins array to see if this plugin
      // is actually activated, and that might be good enough, but
      // for now, we'll use the following approach, because of two
      // concerns: other plugins and other templates might force
      // the inclusion of a plugin (which SHOULD also add it to 
      // the $plugins array, but am not 100% sure at this time (FIXME)),
      // and because the regexps below should work just fine with
      // any resonably formatted plugin setup file.
      //
      // read the target plugin's setup.php file into a string,
      // then use a regular expression to try to find the version...
      // this of course can break if plugin authors do funny things
      // with their file formatting
      //
      $setup_file = '';
      $file_contents = file(SM_PATH . 'plugins/' . $plugin_name . '/setup.php');
      foreach ($file_contents as $line)
         $setup_file .= $line;


      // this regexp grabs a version number from a standard 
      // <plugin>_info() function
      //
      if (preg_match('/[\'"]version[\'"]\s*=>\s*[\'"](.+?)[\'"]/is', $setup_file, $matches))
         $plugin_info = array('version' => $matches[1]);


      // this regexp grabs a version number from a standard 
      // (deprecated) <plugin>_version() function
      //
      else if (preg_match('/function\s+.*?' . $plugin_name . '_version.*?\(.*?\).*?\{.*?return\s+[\'"](.+?)[\'"]/is', $setup_file, $matches))
         $plugin_info = array('version' => $matches[1]);

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
  * @param string plugin_name   Name of the plugin to
  *                             check; must precisely
  *                             match the plugin
  *                             directory name
  * @param int  a               Major version number
  * @param int  b               Minor version number
  * @param int  c               Release number
  * @param bool force_inclusion Try to get version info
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
  * Get a certain plugin requirement.  
  *
  * Attempts to find the given plugin requirement value
  * in the given plugin's informational array, and returns
  * it or NULL if it was not found.
  *
  * Some plugins have different values for the same
  * requirement depending on the SquirrelMail version,
  * and this function is smart enough to take that into
  * account.  
  *
  * By default, the desired plugin must be currently
  * activated, and if it is not, this function will
  * return NULL.  By overriding the default value
  * of $force_inclusion, this function will attempt
  * to grab requirement information from the given
  * plugin even if it is not activated (plugin still
  * has to be unpackaged and set in place in the
  * plugins directory).  Use with care - some plugins
  * might break SquirrelMail when this is used.
  * 
  * @since 1.5.2
  *
  * @param string  $plugin_name         Name of the plugin to
  *                                     check; must precisely
  *                                     match the plugin
  *                                     directory name
  * @param string  $requirement         The desired requirement name
  * @param boolean $ignore_incompatible When TRUE, version incompatibility
  *                                     information will NOT be returned
  *                                     if found; when FALSE, it will be
  *                                     (OPTIONAL; default TRUE)
  * @param boolean $force_inclusion     Try to get requirement info
  *                                     for plugins not activated?
  *                                     (OPTIONAL; default FALSE)
  *
  * @return mixed NULL is returned if the plugin could not be 
  *               found or does not include the given requirement,
  *               the constant SQ_INCOMPATIBLE is returned if the
  *               given plugin is entirely incompatible with the
  *               current SquirrelMail version (unless
  *               $ignore_incompatible is TRUE), otherwise the 
  *               value of the requirement is returned, whatever 
  *               that may be (varies per requirement type).
  *
  */
function get_plugin_requirement($plugin_name, $requirement, 
                                $ignore_incompatible = TRUE,
                                $force_inclusion = FALSE)
{

   $info_function = $plugin_name . '_info';
   $plugin_info = array();
   $requirement_value = NULL;


   // first attempt to find the plugin info function, wherein
   // the plugin requirements should be available
   //
   if (function_exists($info_function))
      $plugin_info = $info_function();
   else if ($force_inclusion 
    && file_exists(SM_PATH . 'plugins/' . $plugin_name . '/setup.php'))
   {

      /* --- Old code, keeping just in case... problem with it is, for example,
         if it is used, but later we are checking if the same plugin is
         activated (because it SHOULD be), this code having run will possibly
         create a false positive.
      include_once(SM_PATH . 'plugins/' . $plugin_name . '/setup.php');
      if (function_exists($info_function))
         $plugin_info = $info_function();
      --- */

      // so what we need to do is process this plugin without
      // it polluting our environment
      //
      // we *could* just use the above code, which is more of a
      // sure thing than a regular expression, and then test
      // the contents of the $plugins array to see if this plugin
      // is actually activated, and that might be good enough, but
      // for now, we'll use the following approach, because of two
      // concerns: other plugins and other templates might force
      // the inclusion of a plugin (which SHOULD also add it to
      // the $plugins array, but am not 100% sure at this time (FIXME)),
      // and because the regexp below should work just fine with
      // any resonably formatted plugin setup file.
      //
      // read the target plugin's setup.php file into a string,
      // then use a regular expression to try to find the needed
      // requirement information...
      // this of course can break if plugin authors do funny things
      // with their file formatting
      //
      $setup_file = '';
      $file_contents = file(SM_PATH . 'plugins/' . $plugin_name . '/setup.php');
      foreach ($file_contents as $line) 
         $setup_file .= $line;


      // this regexp grabs the full plugin info array from a standard 
      // <plugin>_info() function... determining the end of the info 
      // array can fail, but if authors end the array with ");\n"
      // (without quotes), then it should work well, especially because 
      // newlines shouldn't be found inside the array after any ");" 
      // (without quotes)
      //
      if (preg_match('/function\s+.*?' . $plugin_name . '_info.*?\(.*?\).*?\{.*?(array.+?\)\s*;)\s*' . "\n" . '/is', $setup_file, $matches))
         eval('$plugin_info = ' . $matches[1]);

   }


   // attempt to get the requirement from the "global" scope 
   // of the plugin information array
   //
   if (isset($plugin_info[$requirement])
    && !is_null($plugin_info[$requirement]))
      $requirement_value = $plugin_info[$requirement];


   // now, if there is a series of per-version requirements, 
   // check there too
   //
   if (!empty($plugin_info['per_version_requirements']) 
    && is_array($plugin_info['per_version_requirements']))
   {

      // iterate through requirements, where keys are version
      // numbers -- tricky part is knowing the difference between
      // more than one version for which the current SM installation
      // passes the check_sm_version() test... we want the highest one
      //
      $requirement_value_override = NULL;
      $highest_version_array = array();
      foreach ($plugin_info['per_version_requirements'] as $version => $requirement_overrides)
      {

         $version_array = explode('.', $version);
         if (sizeof($version_array) != 3) continue;

         $a = $version_array[0];
         $b = $version_array[1];
         $c = $version_array[2];

         // complicated way to say we are interested in these overrides
         // if the version is applicable to us and if the overrides include
         // the requirement we are looking for, or if the plugin is not
         // compatible with this version of SquirrelMail (unless we are
         // told to ignore such)
         // 
         if (check_sm_version($a, $b, $c) 
          && ((!$ignore_incompatible
            && (!empty($requirement_overrides[SQ_INCOMPATIBLE]) 
             || $requirement_overrides === SQ_INCOMPATIBLE))
           || (is_array($requirement_overrides)
            && isset($requirement_overrides[$requirement])
            && !is_null($requirement_overrides[$requirement]))))
         {

            if (empty($highest_version_array)
             || $highest_version_array[0] < $a
             || ($highest_version_array[0] == $a
             && $highest_version_array[1] < $b)
             || ($highest_version_array[0] == $a 
             && $highest_version_array[1] == $b 
             && $highest_version_array[2] < $c))
            {
               $highest_version_array = $version_array;
               if (!empty($requirement_overrides[SQ_INCOMPATIBLE])
                || $requirement_overrides === SQ_INCOMPATIBLE)
                  $requirement_value_override = SQ_INCOMPATIBLE;
               else
                  $requirement_value_override = $requirement_overrides[$requirement];
            }

         }

      }

      // now grab override if one is available
      //
      if (!is_null($requirement_value_override))
         $requirement_value = $requirement_value_override;

   }

   return $requirement_value;

}

/**
  * Get a plugin's other plugin dependencies.
  *
  * Determines and returns all the other plugins
  * that a given plugin requires, as well as the
  * minimum version numbers of the required plugins
  * and whether or not they need to be activated.
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
  * version formatting under the get_plugin_version()
  * function documentation.
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
  *               the constant SQ_INCOMPATIBLE is returned if 
  *               the given plugin is entirely incompatible 
  *               with the current SquirrelMail version, 
  *               otherwise an array is returned where keys 
  *               are the names of required plugin 
  *               dependencies, and values are arrays again, 
  *               where at least the following keys (and 
  *               corresponding values) will be available: 
  *               'version' - value is the minimum version 
  *               required for that plugin (the format of 
  *               which might vary per the value of $do_parse
  *               as well as if the plugin requires a SquirrelMail
  *               core plugin, in which case it is "CORE" or
  *               "CORE:1.5.2" or similar, or, if the plugin is
  *               actually incompatible (not required) with this
  *               one, the constant SQ_INCOMPATIBLE will be found
  *               here), 'activate' - value is boolean: TRUE
  *               indicates that the plugin must also be activated,
  *               FALSE means that it only needs to be present,
  *               but does not need to be activated.  Note that
  *               the return value might be an empty array,
  *               indicating that the plugin has no dependencies.
  *
  */
function get_plugin_dependencies($plugin_name, $force_inclusion = FALSE, 
                                 $do_parse = TRUE)
{

   $plugin_dependencies = get_plugin_requirement($plugin_name, 
                                                 'required_plugins', 
                                                 FALSE,
                                                 $force_inclusion);

   // the plugin is simply incompatible, no need to continue here
   //
   if ($plugin_dependencies === SQ_INCOMPATIBLE)
      return $plugin_dependencies;


   // not an array of requirements?  wrong format, just return FALSE
   //
   if (!is_array($plugin_dependencies))
      return FALSE;


   // make sure everything is in order...
   //
   if (!empty($plugin_dependencies))
   {

      $new_plugin_dependencies = array();
      foreach ($plugin_dependencies as $plugin_name => $plugin_requirements)
      {

         // if $plugin_requirements isn't an array, this is old-style,
         // where only the version number was given...
         //
         if (is_string($plugin_requirements))
            $plugin_requirements = array('version' => $plugin_requirements,
                                         'activate' => FALSE);


         // trap badly formatted requirements arrays that don't have
         // needed info
         //
         if (!is_array($plugin_requirements) 
          || !isset($plugin_requirements['version']))
            continue;
         if (!isset($plugin_requirements['activate']))
            $plugin_requirements['activate'] = FALSE;


         // parse version into something we understand?
         //
         if ($do_parse && $plugin_requirements['version'] != SQ_INCOMPATIBLE)
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
            if (strpos(strtoupper($plugin_requirements['version']), 'CORE') === 0)
            {
               if (strpos($plugin_requirements['version'], ':') === FALSE)
                  $plugin_requirements['version'] = 'CORE';
               else
                  $plugin_requirements['version']
                     = 'CORE:' . trim(preg_replace(array('/[^0-9.]+.*$/', '/[^0-9.]/'), 
                                         '', substr($plugin_requirements['version'], strpos($plugin_requirements['version'], ':') + 1)), 
                                         '.');
            }
            else
               $plugin_requirements['version']
                  = trim(preg_replace(array('/[^0-9.]+.*$/', '/[^0-9.]/'), 
                                      '', $plugin_requirements['version']), 
                                      '.');

         }

         $new_plugin_dependencies[$plugin_name] = $plugin_requirements;

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
  * up to the proper version, and if they are 
  * activated if required.
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
  *               the constant SQ_INCOMPATIBLE is returned if 
  *               the given plugin is entirely incompatible 
  *               with the current SquirrelMail version, 
  *               otherwise an array of the required plugins
  *               that are either not installed or not up to
  *               the minimum required version.  The array is
  *               keyed by plugin name where values are arrays
  *               again, where at least the following keys (and 
  *               corresponding values) will be available: 
  *               'version' - value is the minimum version 
  *               required for that plugin (in printable, non-
  *               parsed format) or the constant SQ_INCOMPATIBLE,
  *               which indicates that the plugin is actually
  *               incompatible (not required), 'activate' - value
  *               is boolean: TRUE indicates that the plugin must
  *               also be activated, FALSE means that it only needs
  *               to be present, but does not need to be activated.  
  *
  */
function check_plugin_dependencies($plugin_name, $force_inclusion = FALSE)
{

   $dependencies = get_plugin_dependencies($plugin_name, $force_inclusion);
   if (!$dependencies) return TRUE;
   if ($dependencies === SQ_INCOMPATIBLE) return $dependencies;
   $missing_or_bad = array();

   foreach ($dependencies as $depend_name => $depend_requirements)
   {

      // check for core plugins first
      //
      if (strpos(strtoupper($depend_requirements['version']), 'CORE') === 0)
      {

         // see if the plugin is in the core (just check if the directory exists)
         //
         if (!file_exists(SM_PATH . 'plugins/' . $depend_name))
            $missing_or_bad[$depend_name] = $depend_requirements;


         // check if it is activated if need be
         //
         else if ($depend_requirements['activate'] && !is_plugin_enabled($depend_name))
            $missing_or_bad[$depend_name] = $depend_requirements;


         // check if this is the right core version if one is given
         // (note this is pretty useless - a plugin should specify
         // whether or not it itself is compatible with this version
         // of SM in the first place)
         //
         else if (strpos($depend_requirements['version'], ':') !== FALSE)
         {
            $version = explode('.', substr($depend_requirements['version'], strpos($depend_requirements['version'], ':') + 1), 3);
            $version[0] = intval($version[0]);
            if (isset($version[1])) $version[1] = intval($version[1]);
            else $version[1] = 0;
            if (isset($version[2])) $version[2] = intval($version[2]);
            else $version[2] = 0;

            if (!check_sm_version($version[0], $version[1], $version[2]))
               $missing_or_bad[$depend_name] = $depend_requirements;
         }

         continue;

      }

      // if the plugin is actually incompatible; check that it
      // is not activated
      //
      if ($depend_requirements['version'] == SQ_INCOMPATIBLE)
      {

         if (is_plugin_enabled($depend_name))
            $missing_or_bad[$depend_name] = $depend_requirements;

         continue;

      }

      // check for normal plugins
      //
      $version = explode('.', $depend_requirements['version'], 3);
      $version[0] = intval($version[0]);
      if (isset($version[1])) $version[1] = intval($version[1]);
      else $version[1] = 0;
      if (isset($version[2])) $version[2] = intval($version[2]);
      else $version[2] = 0;

      $force_dependency_inclusion = !$depend_requirements['activate'];

      if (!check_plugin_version($depend_name, $version[0], $version[1], 
                                $version[2], $force_dependency_inclusion))
         $missing_or_bad[$depend_name] = $depend_requirements;
   }

   if (empty($missing_or_bad)) return TRUE;


   // get non-parsed required versions
   //
   $non_parsed_dependencies = get_plugin_dependencies($plugin_name, 
                                                      $force_inclusion, 
                                                      FALSE);
   $return_array = array();
   foreach ($missing_or_bad as $depend_name => $ignore)
      $return_array[$depend_name] = $non_parsed_dependencies[$depend_name];

   return $return_array;

}

