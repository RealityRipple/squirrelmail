<?php

/**
 ** plugin.php
 **
 ** This file provides the framework for a plugin architecture.
 **
 ** Plugins will eventually be a way to provide added functionality
 ** without having to patch the SquirrelMail source code. Have some
 ** patients, though, as the these funtions might change in the near
 ** future.
 **
 ** Documentation on how to write plugins might show up some time.
 **
 **/


   $plugin_php = true;

   // This function adds a plugin
   function use_plugin ($name) {
      include ('../plugins/'.$name.'/setup.php');
      $function = 'squirrelmail_plugin_init_'.$name;
      $function();
   }

   // This function executes a hook
   function do_hook ($name) {
      global $squirrelmail_plugin_hooks;
      if (is_array($squirrelmail_plugin_hooks[$name])) {
         reset($squirrelmail_plugin_hooks[$name]);
         
         while (list ($id, $function) = 
                each ($squirrelmail_plugin_hooks[$name])) {
            // Add something to set correct gettext domain for plugin
            $function();
         }
      }
   }

   // On startup, register all plugins configured for use
  if (is_array($plugins))
     while (list ($id, $name) = each ($plugins))
        use_plugin($name);

?>
