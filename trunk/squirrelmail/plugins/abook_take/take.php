<?php /* Modified at 6 places by ri_once */ ?>
<?php
  /**
   **  take.php
   **	
   **	Adds a "taken" address to the address book.  Takes addresses from
   **   incoming mail -- the body, To, From, Cc, or Reply-To.
   **/

   session_start();

   if(!isset($username)) {
      echo "You need a valid user and password to access this page!";
      exit;
   }

   chdir("..");
   if (!isset($config_php))
      /* '_once' Added by ri_once */ include_once("../config/config.php");
   if (!isset($i18n_php))
      /* '_once' Added by ri_once */ include_once("../functions/i18n.php");
   if (!isset($page_header_php))
      /* '_once' Added by ri_once */ include_once("../functions/page_header.php");
   if (!isset($addressbook_php))
      /* '_once' Added by ri_once */ include_once("../functions/addressbook.php");
   if (!isset($strings_php))
      /* '_once' Added by ri_once */ include_once("../functions/strings.php");

   /* '_once' Added by ri_once */ include_once("../src/load_prefs.php");
   
   displayPageHeader($color, "None");
   
   $abook_take_verify = getPref($data_dir, $username, 'abook_take_verify');

?>
<FORM ACTION="../../src/addressbook.php" NAME=f_add METHOD="POST">
<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>
<TR><TH BGCOLOR="<?PHP 
    echo $color[0]; 
?>" ALIGN=CENTER><?PHP
   // open address book, trash errors, skip LDAP
   $abook = addressbook_init(false, true);
   printf(_("Add to %s"), $abook->localbackendname);
?></TH></TR>
</TABLE>
<TABLE BORDER=0 CELLPADDING=1 COLS=2 WIDTH="90%" ALIGN=center>
<?PHP
  $name = 'addaddr';
  
  printf("<TR><TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
     _("Nickname"));
  printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
     "<INPUT NAME=\"%s[nickname]\" SIZE=15 VALUE=\"\">".
     "&nbsp;<SMALL>%s</SMALL></TD></TR>\n",
     $color[4], $name, _("Must be unique"));
  printf("<TR><TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
     _("E-mail address"));
  
  echo "<TD BGCOLOR=\"$color[4]\" ALIGN=left>\n";
  echo '<select name="' . $name . "[email]\">\n";
  foreach ($email as $Val)
  {
      if (valid_email($Val, $abook_take_verify))
      {
          echo '<option value="' . htmlspecialchars($Val) .
              '">' . htmlspecialchars($Val) . "</option>\n";
      }
  }
  echo "</select>\n";
  
  printf("<TR><TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
     _("First name"));
  printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
     "<INPUT NAME=\"%s[firstname]\" SIZE=45 VALUE=\"\"></TD></TR>\n",
     $color[4], $name);
  printf("<TR><TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
     _("Last name"));
  printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
     "<INPUT NAME=\"%s[lastname]\" SIZE=45 VALUE=\"\"></TD></TR>\n",
     $color[4], $name);
  printf("<TR><TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
     _("Additional info"));
  printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
     "<INPUT NAME=\"%s[label]\" SIZE=45 VALUE=\"\"></TD></TR>\n",
     $color[4], $name);

  printf("<TR><TD COLSPAN=2 BGCOLOR=\"%s\" ALIGN=center>\n".
     "<INPUT TYPE=submit NAME=\"%s[SUBMIT]\" VALUE=\"%s\"></TD></TR>\n",
     $color[4], $name, _("Add address"));

      print "</TABLE>\n";
?>
</FORM></BODY>
</HTML>
