<?php

   /**
    **  prefs.php
    **
    **  Copyright (c) 1999-2001 The Squirrelmail Development Team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  This contains functions for manipulating user preferences
    **
    **  $Id$
    **/

   global $prefs_are_cached, $prefs_cache;
   if (!session_is_registered('prefs_are_cached')) {
      $prefs_are_cached = false;
      $prefs_cache = array();
   }

   function cachePrefValues($data_dir, $username) {
       global $prefs_are_cached, $prefs_cache;
       
       if ($prefs_are_cached)
           return;
       
       $filename = $data_dir . $username . '.pref';
       
       if (!file_exists($filename)) {
           printf (_("Preference file, %s, does not exist. Log out, and log back in to create a default preference file."), $filename);
           exit;
       }

       $file = fopen($filename, 'r');

       /** read in all the preferences **/
       $highlight_num = 0;
       while (! feof($file)) {
          $pref = trim(fgets($file, 1024));
          $equalsAt = strpos($pref, '=');
          if ($equalsAt > 0) {
              $Key = substr($pref, 0, $equalsAt);
              $Value = substr($pref, $equalsAt + 1);
              if (substr($Key, 0, 9) == 'highlight') {
                  $Key = 'highlight' . $highlight_num;
                  $highlight_num ++;
              }

              if ($Value != '') {
                  $prefs_cache[$Key] = $Value;
              }
          }
       }
       fclose($file);

       session_unregister('prefs_cache');
       session_register('prefs_cache');
       
       $prefs_are_cached = true;
       session_unregister('prefs_are_cached');
       session_register('prefs_are_cached');
   }
   
   
   /** returns the value for $string **/
   function getPref($data_dir, $username, $string, $default = '') {
      global $prefs_cache;

      cachePrefValues($data_dir, $username);

      if (isset($prefs_cache[$string]))
          return $prefs_cache[$string];
      else
        return $default;
   }


   function savePrefValues($data_dir, $username) {
      global $prefs_cache;
      
      $file = fopen($data_dir . $username . '.pref', 'w');
      foreach ($prefs_cache as $Key => $Value) {
          if (isset($Value)) {
              fwrite($file, $Key . '=' . $Value . "\n");
          }
      }
      fclose($file);
   }


   function removePref($data_dir, $username, $string) {
      global $prefs_cache;
      
      cachePrefValues($data_dir, $username);
      
      if (isset($prefs_cache[$string])) {
          unset($prefs_cache[$string]);
      }
          
      savePrefValues($data_dir, $username);
   }
   
   /** sets the pref, $string, to $set_to **/
   function setPref($data_dir, $username, $string, $set_to) {
      global $prefs_cache;
      
      cachePrefValues($data_dir, $username);
      if (isset($prefs_cache[$string]) && $prefs_cache[$string] == $set_to)
         return;
      if ($set_to === '') {
         removePref($data_dir, $username, $string);
	 return;
      }
      $prefs_cache[$string] = $set_to;
      savePrefValues($data_dir, $username);
   }


   /** This checks if there is a pref file, if there isn't, it will
       create it. **/
   function checkForPrefs($data_dir, $username) {
      $filename = $data_dir . $username . '.pref';
      if (!file_exists($filename)) {
         if (!copy($data_dir . 'default_pref', $filename)) {
            echo _("Error opening ") . $filename;
            exit;
         }
      }
   }


   /** Writes the Signature **/
   function setSig($data_dir, $username, $string) {
      $file = fopen($data_dir . $username . '.sig', 'w');
      fwrite($file, $string);
      fclose($file);
   }



   /** Gets the signature **/
   function getSig($data_dir, $username) {
      $filename = $data_dir . $username . '.sig';
      $sig = '';
      if (file_exists($filename)) {
         $file = fopen($filename, 'r');
         while (!feof($file)) {
            $sig .= fgets($file, 1024);
         }
         fclose($file);
      }
      return $sig;
   }
?>
