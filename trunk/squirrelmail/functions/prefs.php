<?
   /**
    **  prefs.php
    **
    **  This contains functions for manipulating user preferences
    **/

   $prefs_php = true;

   /** returns the value for $string **/
   function getPref($data_dir, $username, $string) {
      $filename = "$data_dir$username.pref";
      if (!file_exists($filename)) {
         echo _("Preference file ") . "\"$filename\"" . _(" not found.  Exiting abnormally");
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

   /** sets the pref, $string, to $set_to **/
   function setPref($data_dir, $username, $string, $set_to) {
      $filename = "$data_dir$username.pref";
      $found = false;
      if (!file_exists($filename)) {
         echo _("Preference file, ") . "\"$filename\"" . _(", does not exist.  Log out, and log back in to create a default preference file. ") ."<BR>";
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
      if (file_exists($filename)) {
         $file = fopen($filename, "r");
         while (!feof($file)) {
            $sig .= fgets($file, 1024);
         }
         fclose($file);
      } else {
         echo _("Signature file not found.");
         exit;
      }
      return $sig;
   }
?>
