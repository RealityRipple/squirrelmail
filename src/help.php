<?php
   /**
    **  help.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays help for the user
    **
    **/

   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($array_php))
      include("../functions/array.php");
   if (!isset($i18n_php))
      include("../functions/i18n.php");
   if (!isset($auth_php))
      include ("../functions/auth.php"); 

   include("../src/load_prefs.php");
   displayPageHeader($color, "None");
   is_logged_in(); 

	$helpdir[0] = "basic.hlp";
	$helpdir[1] = "main_folder.hlp";
	$helpdir[2] = "read_mail.hlp";
	$helpdir[3] = "compose.hlp";
	$helpdir[4] = "addresses.hlp";
	$helpdir[5] = "folders.hlp";
	$helpdir[6] = "options.hlp";
	$helpdir[7] = "FAQ.hlp";

   /****************[ HELP FUNCTIONS ]********************/
   // parses through and gets the information from the different documents.  
   // this returns one section at a time.  You must keep track of the position
   // so that it knows where to start to look for the next section.

   function get_info($doc, $pos) {
      for ($n=$pos; $n < count($doc); $n++) {
         if (trim(strtolower($doc[$n])) == "<chapter>" || trim(strtolower($doc[$n])) == "<section>") {
            for ($n++;$n < count($doc) && (trim(strtolower($doc[$n])) != "</section>") && (trim(strtolower($doc[$n])) != "</chapter>"); $n++) {
               if (trim(strtolower($doc[$n])) == "<title>") {
                  $n++;
                  $ary[0] = trim($doc[$n]);
               }
               if (trim(strtolower($doc[$n])) == "<description>") {
                  for ($n++;$n < count($doc) && (trim(strtolower($doc[$n])) != "</description>"); $n++) {
                     $ary[1] .= $doc[$n];
                  }
               }
               if (trim(strtolower($doc[$n])) == "<summary>") {
                  for ($n++;$n < count($doc) && (trim(strtolower($doc[$n])) != "</summary>"); $n++) {
                     $ary[2] .= $doc[$n];
                  }
               }
            }   
            if ($ary) {
               $ary[3] = $n;
               return $ary;
            } else {
               $ary[0] = "ERROR: Help files are not in the right format!";
               $ary[1] = "ERROR: Help files are not in the right format!";
               $ary[2] = "ERROR: Help files are not in the right format!";
               return $ary;
            }   
         }
      }
      $ary[0] = "ERROR: Help files are not in the right format!";
      $ary[1] = "ERROR: Help files are not in the right format!";
      return $ary;
   }
   
   /**************[ END HELP FUNCTIONS ]******************/

?>

<br>
<table width=95% align=center cellpadding=2 cellspacing=2 border=0>
<tr><td bgcolor="<?php echo $color[0] ?>">
   <center><b><?php echo _("Help") ?></b></center>
</td></tr></table>


<table width=90% cellpadding=0 cellspacing=10 border=0 align=center><tr><td>
<?php
   if ($HTTP_REFERER) {
      $ref = strtolower($HTTP_REFERER);
      if (strpos($ref, "src/compose"))
         $context = "compose"; 
      else if (strpos($ref, "src/addr"))
         $context = "address"; 
      else if (strpos($ref, "src/folders"))
         $context = "folders"; 
      else if (strpos($ref, "src/options"))
         $context = "options"; 
      else if (strpos($ref, "src/right_main"))
         $context = "index"; 
      else if (strpos($ref, "src/read_body"))
         $context = "read"; 
   }
   
   if (file_exists("../help/$squirrelmail_language")) {
      $help_exists = true;
      $user_language = $squirrelmail_language;
   } else if (file_exists("../help/en")) {
      $help_exists = true;
      echo "<center><font color=\"$color[2]\">";
      printf (_("The help has not been translated to %s.  It will be displayed in English instead."), $languages[$squirrelmail_language]["NAME"]);
      echo "</font></center><br>";
      $user_language = "en";
   } else {
      $help_exists = false;
      echo "<br><center><font color=\"$color[2]\">";
      echo _("Some or all of the help documents are not present!");
      echo "</font></center>";
      echo "</td></tr></table>";
      exit;
   }
   
   if ($help_exists) {
      if ($context == "compose")
         $chapter = 4;
      else if ($context == "address")
         $chapter = 5;
      else if ($context == "folders")
         $chapter = 6;
      else if ($context == "options")
         $chapter = 7;
      else if ($context == "index")
         $chapter = 2;
      else if ($context == "read")
         $chapter = 3;

      if (!$chapter) {
         echo "<table cellpadding=0 cellspacing=0 border=0 align=center><tr><td>\n";
         echo "<b><center>" . _("Table of Contents") . "</center></b><br>";
         echo "<ol>\n";
         for ($i=0; $i < count($helpdir); $i++) {
            $doc = file("../help/$user_language/$helpdir[$i]");
            $help_info = get_info($doc, 0);
            echo "<li><a href=\"../src/help.php?chapter=". ($i+1) ."\">$help_info[0]</a>\n";
            echo "<ul>$help_info[2]</ul>";
         }
         echo "</ol>\n";
         echo "</td></tr></table>\n";
      } else {
         $doc = file("../help/$user_language/".$helpdir[$chapter-1]);
         $help_info = get_info($doc, 0);

         echo "<small><center>";

         if ($chapter <= 1) echo "<font color=\"$color[9]\">Previous</font> | ";
         else echo "<a href=\"../src/help.php?chapter=".($chapter-1)."\">Previous</a> | ";
         echo "<a href=\"../src/help.php\">Table of Contents</a>";
         if ($chapter >= count($helpdir)) echo " | <font color=\"$color[9]\">Next</font>";
         else echo " | <a href=\"../src/help.php?chapter=".($chapter+1)."\">Next</a>";
         echo "</center></small><br>\n";

         echo "<font size=5><b>$chapter - $help_info[0]</b></font><br><br>\n";
         if ($help_info[1])
            echo "$help_info[1]";
         else   
            echo "<p>$help_info[2]</p>";

         for ($n = $help_info[3]; $n < count($doc); $n++) {
            $section++;
            $help_info = get_info($doc, $n);
            echo "<b>$chapter.$section - $help_info[0]</b>";
            echo "<ul>";
            echo "$help_info[1]";
            echo "</ul>";
            $n = $help_info[3];
         }

         echo "<br><center><a href=\"#pagetop\">" . _("Top") . "</a></center>";
      }
   }
?>
<tr><td bgcolor="<?php echo $color[0] ?>">&nbsp;</td></tr></table>
<td></tr></table>
</body></html>
