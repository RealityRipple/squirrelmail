<?php
   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($i18n_php))
      include("../functions/i18n.php");
   include("../src/load_prefs.php");

   displayPageHeader($color, "None");

   // MESSAGE HIGHLIGHTING
   echo "<br>\n";
   echo "<center><b>" . _("Message Highlighting") . "</b></center><br>\n";


   if ($action == "save") {
      if (!$id) $id = 0;
      setPref($data_dir, $username, "highlight$id", $name.",".$newcolor.",".$value);
      echo "<a href=\"options.php\">saved</a>";
   } else {
      if (!isset($id)) $id = count($message_highlight_list);
      
      echo "<form action=\"msg_highlight.php\">\n";
      echo "<input type=\"hidden\" value=\"save\" name=\"action\">\n";
      echo "<input type=\"hidden\" value=\"$id\" name=\"id\">\n";
      echo "<table width=75% cellpadding=2 cellspacing=0 border=0>\n";
      echo "   <tr>\n";
      echo "      <td>\n";
      echo _("Identifying name") . ":";
      echo "      </td>\n";
      echo "      <td>\n";
      echo "         <input type=\"text\" value=\"".$message_highlight_list[$id]["name"]."\" name=\"name\">";
      echo "      </td>\n";
      echo "   </tr>\n";
      echo "   <tr>\n";
      echo "      <td>\n";
      echo _("Color") . ":";
      echo "      </td>\n";
      echo "      <td>\n";
      echo "         <input type=\"text\" value=\"".$message_highlight_list[$id]["color"]."\" name=\"newcolor\">";
      echo "      </td>\n";
      echo "   </tr>\n";
      echo "   <tr>\n";
      echo "      <td>\n";
      echo _("Match") . ":";
      echo "      </td>\n";
      echo "      <td>\n";
      echo "         <input type=\"text\" value=\"".$message_highlight_list[$id]["value"]."\" name=\"value\">";
      echo "      </td>\n";
      echo "   </tr>\n";
      echo "</table>\n";
      echo "<center><input type=\"submit\" value=\"" . _("Submit") . "\"></center>\n";
      echo "</form>\n";
   }   
   echo "</BODY></HTML>";
?>
