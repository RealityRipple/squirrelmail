<?php
   /**
    **  prefs.php
    **
    **  This contains functions for manipulating user preferences
    **
    **  $Id$
    **/

   $prefs_php = true;

   /** returns the value for $string **/
   function getPref($data_dir, $username, $string) {
      $filename = "$data_dir$username.pref";
      if (!file_exists($filename)) {
	 printf (_("Preference file %s not found. Exiting abnormally"), $filename);
         exit;
      }

      $file = fopen($filename, "r");

      /** read in all the preferences **/
      for ($i=0; !feof($file); $i++) {
         $pref = fgets($file, 1024);
         if (substr($pref, 0, strpos($pref, "=")) == $string) {
            fclose($file);
            return trim(substr($pref, strpos($pref, "=")+1));
         }
      }
      fclose($file);
      return "";
   }

   function removePref($data_dir, $username, $string) {
      $filename = "$data_dir$username.pref";
      $found = false;
      if (!file_exists($filename)) {
	 printf (_("Preference file, %s, does not exist. Log out, and log back in to create a default preference file."), $filename);
	 echo "<br>\n";
         exit;
      }
      $file = fopen($filename, "r");

      for ($i=0; !feof($file); $i++) {
         $pref[$i] = fgets($file, 1024);
         if (substr($pref[$i], 0, strpos($pref[$i], "=")) == $string) {
            $i--;
         }
      }
      fclose($file);

      for ($i=0,$j=0; $i < count($pref); $i++) {
         if (substr($pref[$i], 0, 9) == "highlight") {
            $hlt[$j] = substr($pref[$i], strpos($pref[$i], "=")+1);
            $j++;
         }
      }

      $file = fopen($filename, "w");
      for ($i=0; $i < count($pref); $i++) {
         if (substr($pref[$i], 0, 9) != "highlight") {
            fwrite($file, "$pref[$i]", 1024);
         }   
      }
      if (isset($htl)) {
         for ($i=0; $i < count($hlt); $i++) {
            fwrite($file, "highlight$i=$hlt[$i]");
         }
      }
      fclose($file);
   }
   
   /** sets the pref, $string, to $set_to **/
   function setPref($data_dir, $username, $string, $set_to) {
      $filename = "$data_dir$username.pref";
      $found = false;
      if (!file_exists($filename)) {
	 printf (_("Preference file, %s, does not exist. Log out, and log back in to create a default preference file."), $filename);
	 echo "\n<br>\n";
         exit;
      }
      $file = fopen($filename, "r");

      /** read in all the preferences **/
      for ($i=0; !feof($file); $i++) {
         $pref[$i] = fgets($file, 1024);
         if (substr($pref[$i], 0, strpos($pref[$i], "=")) == $string) {
            $found = true;
            $pos = $i;
         }
      }
      fclose($file);

      $file = fopen($filename, "w");
      if ($found == true) {
         for ($i=0; $i < count($pref); $i++) {
            if ($i == $pos) {
               fwrite($file, "$string=$set_to\n", 1024);
            } else {
               fwrite($file, "$pref[$i]", 1024);
            }
         }
      } else {
         for ($i=0; $i < count($pref); $i++) {
            fwrite($file, "$pref[$i]", 1024);
         }
         fwrite($file, "$string=$set_to\n", 1024);
      }

      fclose($file);
   }




   /** This checks if there is a pref file, if there isn't, it will
       create it. **/
   function checkForPrefs($data_dir, $username) {
      $filename = "$data_dir$username.pref";
      if (!file_exists($filename)) {
         if (!copy("$data_dir" . "default_pref", $filename)) {
            echo _("Error opening ") ."$filename";
            exit;
         }
      }
   }



   /** Writes the Signature **/
   function setSig($data_dir, $username, $string) {
      $filename = "$data_dir$username.sig";
      $file = fopen($filename, "w");
      fwrite($file, $string);
      fclose($file);
   }



   /** Gets the signature **/
   function getSig($data_dir, $username) {
      $filename = "$data_dir$username.sig";
      $sig = "";
      if (file_exists($filename)) {
         $file = fopen($filename, "r");
         while (!feof($file)) {
            $sig .= fgets($file, 1024);
         }
         fclose($file);
      }
      return $sig;
   }
?>
