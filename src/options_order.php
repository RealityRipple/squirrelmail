<?php
   /**
    **  options_highlight.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays message highlighting options
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
   if (!isset($plugin_php))
      include("../functions/plugin.php");


   if ($action == "delete" && isset($theid)) {
      removePref($data_dir, $username, "highlight$theid");
   } else if ($action == "save") {
   } 
   include("../src/load_prefs.php");
   displayPageHeader($color, "None");
?>
   <br>
   <table width=95% align=center border=0 cellpadding=2 cellspacing=0><tr><td bgcolor="<?php echo $color[0] ?>">
      <center><b><?php echo _("Options") . " - " . _("Index Order"); ?></b></center>
   </td></tr></table>

   <table width=95% align=center border=0><tr><td>
<?php

   $available[1] = "Checkbox";
   $available[2] = "From";
   $available[3] = "Date";
   $available[4] = "Subject";
   $available[5] = "Flags";
   $available[6] = "Size";
   
   if ($method == "up" && $num > 1) {
      $prev = $num-1;
      $tmp = $index_order[$prev];
      $index_order[$prev] = $index_order[$num];
      $index_order[$num] = $tmp;
   } else if ($method == "down" && $num < count($index_order)) {
      $next = $num++;
      $tmp = $index_order[$next];
      $index_order[$next] = $index_order[$num];
      $index_order[$num] = $tmp;
   } else if ($method == "remove" && $num) {
      for ($i=1; $i < 8; $i++) {
         removePref($data_dir, $username, "order$i"); 
      }
      for ($j=1,$i=1; $i <= count($index_order); $i++) {
         if ($i != $num) {
            $new_ary[$j] = $index_order[$i];
            $j++;
         }
      }
      $index_order = array();
      $index_order = $new_ary;
      if (count($index_order) < 1) {
         include "../src/load_prefs.php";
      }
   } else if ($method == "add" && $add) {
      $index_order[count($index_order)+1] = $add;
   }

   if ($method) {
      for ($i=1; $i <= count($index_order); $i++) {
         setPref($data_dir, $username, "order$i", $index_order[$i]);
      }
   }

   for ($i=1; $i <= count($index_order); $i++) {
      $tmp = $index_order[$i];
      echo "<small><a href=\"options_order.php?method=up&num=$i\">up</a> | ";
      echo "<a href=\"options_order.php?method=down&num=$i\">down</a> | ";
      echo "<a href=\"options_order.php?method=remove&num=$i\">remove</a></small> - ";
      echo $available[$tmp] . "<br>";
   }
   
   if (count($index_order) != count($available)) {
   echo "<form name=f method=post action=options_order.php>";
   echo "<select name=add>";
   for ($i=1; $i <= count($available); $i++) {
      $found = false;
      for ($j=1; $j <= count($index_order); $j++) {
         if ($index_order[$j] == $i) {
            $found = true;
         }
      }
      if (!$found) {
         echo "<option value=$i>$available[$i]</option>";
      }
   }
   echo "</select>";
   echo "<input type=hidden value=add name=method>";
   echo "<input type=submit value=\""._("Add")."\" name=submit>";
   echo "</form>";
   }

?>
   </td></tr></table>
</body></html>
