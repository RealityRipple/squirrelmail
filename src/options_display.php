<?php
   /**
    **  options_display.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays all optinos about display preferences
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

   include("../src/load_prefs.php");
   displayPageHeader($color, "None");
   $chosen_language = getPref($data_dir, $username, "language");  
?>
   <br>
   <table width=95% align=center border=0 cellpadding=2 cellspacing=0><tr><td bgcolor="<?php echo $color[0] ?>">
      <center><b><?php echo _("Options") . " - " . _("Display Preferences"); ?></b></center>
   </td></tr></table>

   <form action="options.php" method=post>
      <table width=100% cellpadding=0 cellspacing=2 border=0>
         <tr>
            <td align=right nowrap><?php echo _("Theme"); ?>:
            </td><td>
<?php
   echo "         <tt><select name=chosentheme>\n";
   for ($i = 0; $i < count($theme); $i++) {
      if ($theme[$i]["PATH"] == $chosen_theme)
         echo "         <option selected value=\"".$theme[$i]["PATH"]."\">".$theme[$i]["NAME"]."\n";
      else
         echo "         <option value=\"".$theme[$i]["PATH"]."\">".$theme[$i]["NAME"]."\n";
   }
   echo "         </select></tt>";  
?>
            </td>
         </tr>
         <tr>
            <td align=right nowrap><?php echo _("Language"); ?>:
            </td><td>
<?php
   echo "         <tt><select name=language>\n";
   reset ($languages);
   while (list($code, $name)=each($languages)) {
      if ($code==$chosen_language)
         echo "         <OPTION SELECTED VALUE=\"".$code."\">".$languages[$code]["NAME"]."\n";
      else
         echo "         <OPTION VALUE=\"".$code."\">".$languages[$code]["NAME"]."\n";
 
   } 
   echo "         </select></tt>";  
?>
            </td>
         <tr>
            <td align=right nowrap>&nbsp;
            </td><td>
               <?php echo _("Use Javascript or HTML addressbook?") . "<br>"; 
               if ($use_javascript_addr_book == true) {
                  echo "         <input type=radio name=javascript_abook value=1 checked> " . _("JavaScript") . "&nbsp;&nbsp;&nbsp;&nbsp;";
                  echo "         <input type=radio name=javascript_abook value=0> " . _("HTML"); 
               } else {
                  echo "         <input type=radio name=javascript_abook value=1> " . _("JavaScript") . "&nbsp;&nbsp;&nbsp;&nbsp;";
                  echo "         <input type=radio name=javascript_abook value=0 checked> " . _("HTML"); 
               }  
               ?>
            </td>
         </tr>
         <tr>
            <td align=right nowrap><?php echo _("Number of Messages to Index"); ?>:
            </td><td>
<?php
   if (isset($show_num))
      echo "         <tt><input type=text size=5 name=shownum value=\"$show_num\"></tt><br>";
   else
      echo "         <tt><input type=text size=5 name=shownum value=\"25\"></tt><br>"; 
?>
            </td>
         </tr>
         <tr>
            <td align=right nowrap><?php echo _("Wrap incoming text at"); ?>:
            </td><td>
<?php
   if (isset($wrap_at))
      echo "         <tt><input type=text size=5 name=wrapat value=\"$wrap_at\"></tt><br>";
   else
      echo "         <tt><input type=text size=5 name=wrapat value=\"86\"></tt><br>"; 
?>
            </td>
         </tr>
         <tr>
            <td align=right nowrap><?php echo _("Size of editor window"); ?>:
            </td><td>
<?php
   if ($editor_size >= 10 && $editor_size <= 255)
      echo "         <tt><input type=text size=5 name=editorsize value=\"$editor_size\"></tt><br>";
   else
      echo "         <tt><input type=text size=5 name=editorsize value=\"76\"></tt><br>"; 
?>
            </td>
         </tr>
         <tr>
            <td align=right nowrap><?php echo _("Width of left folder list"); ?>:
            </td><td>
<?php
   echo "         <select name=leftsize>\n";
   if ($left_size == 100)
      echo "<option value=100 selected>100 pixels\n";
   else
      echo "<option value=100>100 pixels\n";
 
   if ($left_size == 125)
      echo "<option value=125 selected>125 pixels\n";
   else
      echo "<option value=125>125 pixels\n";
 
   if ($left_size == 150)
      echo "<option value=150 selected>150 pixels\n";
   else
      echo "<option value=150>150 pixels\n";
 
   if ($left_size == 175)
      echo "<option value=175 selected>175 pixels\n";
   else
      echo "<option value=175>175 pixels\n";
 
   if (($left_size == 200) || ($left_size == ""))
      echo "<option value=200 selected>200 pixels\n";
   else
      echo "<option value=200>200 pixels\n";
 
   if (($left_size == 225))
      echo "<option value=225 selected>225 pixels\n";
   else
      echo "<option value=225>225 pixels\n";
 
   if (($left_size == 250))
      echo "<option value=250 selected>250 pixels\n";
   else
      echo "<option value=250>250 pixels\n";
 
   if ($left_size == 275)
      echo "<option value=275 selected>275 pixels\n";
   else
      echo "<option value=275>275 pixels\n";
 
   if (($left_size == 300))
      echo "<option value=300 selected>300 pixels\n";
   else
      echo "<option value=300>300 pixels\n";
 
   echo "         </select>";  
?>
            </td>
         </tr>
         <tr>
            <td align=right nowrap><?php echo _("Auto refresh folder list"); ?>:
            </td><td>
<?php
   echo "               <SELECT name=leftrefresh>";
   if (($left_refresh == "None") || ($left_refresh == ""))
      echo "                  <OPTION VALUE=None SELECTED>None";
   else
      echo "                  <OPTION VALUE=None>None";
 
   if (($left_refresh == "10"))
      echo "                  <OPTION VALUE=10 SELECTED>10 Seconds";
   else
      echo "                  <OPTION VALUE=10>10 Seconds";
 
   if (($left_refresh == "20"))
      echo "                  <OPTION VALUE=20 SELECTED>20 Seconds";
   else
      echo "                  <OPTION VALUE=20>20 Seconds";
 
   if (($left_refresh == "30"))
      echo "                  <OPTION VALUE=30 SELECTED>30 Seconds";
   else
      echo "                  <OPTION VALUE=30>30 Seconds";
 
   if (($left_refresh == "60"))
      echo "                  <OPTION VALUE=60 SELECTED>1 Minute";
   else
      echo "                  <OPTION VALUE=60>1 Minute";
 
   if (($left_refresh == "120"))
      echo "                  <OPTION VALUE=120 SELECTED>2 Minutes";
   else
      echo "                  <OPTION VALUE=120>2 Minutes";
 
   if (($left_refresh == "180"))
      echo "                  <OPTION VALUE=180 SELECTED>3 Minutes";
   else
      echo "                  <OPTION VALUE=180>3 Minutes";
 
   if (($left_refresh == "240"))
      echo "                  <OPTION VALUE=240 SELECTED>4 Minutes";
   else
      echo "                  <OPTION VALUE=240>4 Minutes";
 
   if (($left_refresh == "300"))
      echo "                  <OPTION VALUE=300 SELECTED>5 Minutes";
   else
      echo "                  <OPTION VALUE=300>5 Minutes";
 
   if (($left_refresh == "420"))
      echo "                  <OPTION VALUE=420 SELECTED>7 Minutes";
   else
      echo "                  <OPTION VALUE=420>7 Minutes";

   if (($left_refresh == "600"))
      echo "                  <OPTION VALUE=600 SELECTED>10 Minutes";
   else
      echo "                  <OPTION VALUE=600>10 Minutes";
 
   if (($left_refresh == "720"))
      echo "                  <OPTION VALUE=720 SELECTED>12 Minutes";
   else
      echo "                  <OPTION VALUE=720>12 Minutes";
 
   if (($left_refresh == "900"))
      echo "                  <OPTION VALUE=900 SELECTED>15 Minutes";
   else
      echo "                  <OPTION VALUE=900>15 Minutes";
 
   if (($left_refresh == "1200"))
      echo "                  <OPTION VALUE=1200 SELECTED>20 Minutes";
   else
      echo "                  <OPTION VALUE=1200>20 Minutes";
 
   if (($left_refresh == "1500"))
      echo "                  <OPTION VALUE=1500 SELECTED>25 Minutes";
   else
      echo "                  <OPTION VALUE=1500>25 Minutes";
 
   if (($left_refresh == "1800"))
      echo "                  <OPTION VALUE=1800 SELECTED>30 Minutes";
   else
      echo "                  <OPTION VALUE=1800>30 Minutes";
 
      echo "               </SELECT>"; 
?>
            </td>
         </tr>
         <tr>
            <td>&nbsp;
            </td><td>
               <input type="submit" value="Submit" name="submit_display">
            </td>
         </tr>
      </table>   
   </form>
</body></html>
