<?php
   /**
    **  addressbook.php
    **
    **  Manage personal address book.
    **
    **/

   if(!isset($logged_in)) {
      echo _("You must login first.");
      exit;
   }
   if(!isset($username) || !isset($key)) {
      echo _("You need a valid user and password to access this page!");
      exit;
   }

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($array_php))
      include("../functions/array.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");
   if (!isset($addressbook_php))
      include("../functions/addressbook.php");


   // Sort array by the key "name"
   function alistcmp($a,$b) {   
      return (strtolower($a["name"]) > strtolower($b["name"])) ? 1 : -1;
   }

   // Output form to add and modify address data
   function address_form($name, $submittext, $values = array()) {
      global $color;
      print "<TABLE BORDER=0 CELLPADDING=1 COLS=2 WIDTH=\"90%\" ALIGN=center>\n";
      printf("<TR><TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
	     _("Nickname"));
      printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
	     "<INPUT NAME=\"%s[nickname]\" SIZE=15 VALUE=\"%s\">".
	     "&nbsp;<SMALL>%s</SMALL></TD></TR>\n",
	     $color[4], $name, htmlspecialchars($values["nickname"]), 
	     _("Must be unique"));
      printf("<TR><TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
	     _("E-mail address"));
      printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
	     "<INPUT NAME=\"%s[email]\" SIZE=45 VALUE=\"%s\"></TD></TR>\n",
	     $color[4], $name, htmlspecialchars($values["email"]));
      printf("<TR><TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
	     _("First name"));
      printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
	     "<INPUT NAME=\"%s[firstname]\" SIZE=45 VALUE=\"%s\"></TD></TR>\n",
	     $color[4], $name, htmlspecialchars($values["firstname"]));
      printf("<TR><TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
	     _("Last name"));
      printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
	     "<INPUT NAME=\"%s[lastname]\" SIZE=45 VALUE=\"%s\"></TD></TR>\n",
	     $color[4], $name, htmlspecialchars($values["lastname"]));
      printf("<TR><TD WIDTH=50 BGCOLOR=\"$color[4]\" ALIGN=RIGHT>%s:</TD>",
	     _("Additional info"));
      printf("<TD BGCOLOR=\"%s\" ALIGN=left>".
	     "<INPUT NAME=\"%s[label]\" SIZE=45 VALUE=\"%s\"></TD></TR>\n",
	     $color[4], $name, htmlspecialchars($values["label"]));

      printf("<TR><TD COLSPAN=2 BGCOLOR=\"%s\" ALIGN=center>\n".
	     "<INPUT TYPE=submit NAME=\"%s[SUBMIT]\" VALUE=\"%s\"></TD></TR>\n",
	     $color[4], $name, $submittext);

      print "</TABLE>\n";
   }


   // IMAP Login
   $imapConnection = sqimap_login ($username, $key, 
	                           $imapServerAddress, $imapPort, 10);
   include("../src/load_prefs.php");
   sqimap_logout ($imapConnection);


   // Open addressbook, with error messages on but without LDAP (the
   // second "true"). Don't need LDAP here anyway
   $abook = addressbook_init(true, true);
   if($abook->localbackend == 0) {
      plain_error_message(_("No personal address book is defined. Contact administrator."), $color);
      exit();
   }

   print "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">\n";
   print "<HTML><HEAD><TITLE>\n";
   printf("%s: %s\n", $org_title, _("Address Book")); 
   print "</TITLE></HEAD>\n\n";

   printf('<BODY TEXT="%s" BGCOLOR="%s" LINK="%s" VLINK="%s" ALINK="%s">',
	  $color[8], $color[4], $color[7], $color[7], $color[7]);
   displayPageHeader($color, "None");


   $defdata   = array();
   $formerror = "";
   $abortform = false;
   $showaddrlist = true;


   // Handle user's actions
   if($REQUEST_METHOD == "POST") {

      // Check for user addition
      $add_data = $HTTP_POST_VARS["addaddr"];
      if(!empty($add_data["nickname"])) {
	 
	 $r = $abook->add($add_data, $abook->localbackend);

	 // Handle error messages
	 if(!$r) {
	    // Remove backend name from error string
	    $errstr = $abook->error;
	    $errstr = ereg_replace("^\[.*\] *", "", $errstr);

	    $formerror = $errstr;
	    $showaddrlist = false;
	    $defdata = $add_data;
	 }
   
      }

      // Check for "delete address"
      if((!empty($HTTP_POST_VARS["deladdr"])) &&
         sizeof($HTTP_POST_VARS["sel"]) > 0) {
	 plain_error_message("Delete address not implemented yet", $color);
	 $abortform = true;
      }

      // Check for "edit address"
      if((!empty($HTTP_POST_VARS["editaddr"])) &&
         sizeof($HTTP_POST_VARS["sel"]) > 0) {
	 plain_error_message("Edit address not implemented yet", $color);
	 $abortform = true;
      }

      // Some times we end output before forms are printed 
      if($abortform) {
	 print "</BODY></HTML>\n";
	 exit();
      }

   }

   // ===================================================================
   // The following is only executed on a GET request, or on a POST when
   // a user is added, or when "delete" or "modify" was successful.
   // ===================================================================

   // Display error messages
   if(!empty($formerror)) {
      print "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
      print "<TR><TD ALIGN=CENTER>\n<br><STRONG>";
      print "<FONT COLOR=\"$color[2]\">"._("ERROR").": $formerror</FONT>";
      print "<STRONG>\n</TD></TR>\n";
      print "</TABLE>\n";
   }

   // Display the address management part
   if($showaddrlist) {
      printf("<FORM ACTION=\"%s\" METHOD=\"POST\">\n", $PHP_SELF);

      print "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
      print "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n<STRONG>";
      print _("Personal address book");
      print "<STRONG>\n</TD></TR>\n";
      print "</TABLE>\n";
      
      // Get and sort address list
      $alist = $abook->list_addr();
      usort($alist,'alistcmp');
      
      print "<table cols=5 border=0 width=\"90%\" align=center>";
      printf("<tr bgcolor=\"$color[9]\"><TH align=left width=\"3%%\">&nbsp;".
	     "<TH align=left width=\"10%%\">%s<TH align=left>%s<TH align=left>%s".
	     "<TH align=left>%s</TR>\n",
	     _("Nickname"), _("Name"), _("E-mail"), _("Info"));
      while(list($key,$row) = each($alist)) {
	 printf("<TR%s NOWRAP><TD align=center><small>".
		"<INPUT TYPE=checkbox NAME=\"sel[]\" VALUE=\"%s\"></small>".
		"<TD NOWRAP>&nbsp;%s&nbsp;<TD NOWRAP>&nbsp;%s&nbsp;".
		"<TD NOWRAP>&nbsp;<A HREF=\"compose.php?send_to=%s\">%s</A>&nbsp;".
		"<TD NOWRAP>&nbsp;%s</TR>\n", 
		($line % 2) ? " bgcolor=\"$color[0]\"" : "", $row["nickname"],
		$row["nickname"], $row["name"], rawurlencode($row["email"]), 
		$row["email"], $row["label"]);
	 $line++;
      }
      print "<TR><TD COLSPAN=5 ALIGN=center>\n";
      printf("<INPUT TYPE=submit NAME=editaddr VALUE=\"%s\">\n",
	     _("Edit selected"));
      printf("<INPUT TYPE=submit NAME=deladdr VALUE=\"%s\">\n",
	     _("Delete selected"));
      print "</TR></TABLE></FORM>";
   }      

   // Display the "new address" form
   printf("<FORM ACTION=\"%s\" METHOD=\"POST\">\n", $PHP_SELF);
   print "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
   print "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n<STRONG>";
   print _("Add to personal address book");
   print "<STRONG>\n</TD></TR>\n";
   print "</TABLE>\n";
   address_form("addaddr", _("Add address"), $defdata);
   print "</FORM>";

?>

</BODY></HTML>
