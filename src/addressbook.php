<?php
   /**
    **  addressbook.php
    **
    **  Manage personal address book.
    **
    **/

   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($array_php))
      include("../functions/array.php");
   if (!isset($auth_php))
      include("../functions/auth.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");
   if (!isset($addressbook_php))
      include("../functions/addressbook.php");

   is_logged_in();

   // Sort array by the key "name"
   function alistcmp($a,$b) {   
      if($a["backend"] > $b["backend"]) 
	 return 1;
      else if($a["backend"] < $b["backend"]) 
	 return -1;
      
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


   include("../src/load_prefs.php");

   // Open addressbook, with error messages on but without LDAP (the
   // second "true"). Don't need LDAP here anyway
   $abook = addressbook_init(true, true);
   if($abook->localbackend == 0) {
      plain_error_message(_("No personal address book is defined. Contact administrator."), $color);
      exit();
   }

   displayPageHeader($color, "None");


   $defdata   = array();
   $formerror = "";
   $abortform = false;
   $showaddrlist = true;
   $defselected  = array();


   // Handle user's actions
   if($REQUEST_METHOD == "POST") {

      // ***********************************************
      // Add new address
      // ***********************************************
      if(!empty($addaddr["nickname"])) {
	 
	 $r = $abook->add($addaddr, $abook->localbackend);

	 // Handle error messages
	 if(!$r) {
	    // Remove backend name from error string
	    $errstr = $abook->error;
	    $errstr = ereg_replace("^\[.*\] *", "", $errstr);

	    $formerror = $errstr;
	    $showaddrlist = false;
	    $defdata = $addaddr;
	 }
   
      } 


      // ***********************************************
      // Delete address(es)
      // ***********************************************
      else if((!empty($deladdr)) &&
         sizeof($sel) > 0) {
	 $orig_sel = $sel;
	 sort($sel);

	 // The selected addresses are identidied by "backend:nickname".
	 // Sort the list and process one backend at the time
	 $prevback  = -1;
	 $subsel    = array();
	 $delfailed = false;

	 for($i = 0 ; (($i < sizeof($sel)) && !$delfailed) ; $i++) {
	    list($sbackend, $snick) = split(":", $sel[$i]);

	    // When we get to a new backend, process addresses in
	    // previous one.
	    if($prevback != $sbackend && $prevback != -1) {

	       $r = $abook->remove($subsel, $prevback);
	       if(!$r) { 
		  $formerror = $abook->error;
		  $i = sizeof($sel);
		  $delfailed = true;
		  break;
	       }
	       $subsel   = array();
	    }

	    // Queue for processing
	    array_push($subsel, $snick);	    
	    $prevback = $sbackend;
	 }
	 
	 if(!$delfailed) {
	    $r = $abook->remove($subsel, $prevback);
	    if(!$r) { // Handle errors
	       $formerror = $abook->error;
	       $delfailed = true;
	    }
	 }

	 if($delfailed) {
	    $showaddrlist = true;
	    $defselected  = $orig_sel;
	 }
      }


      // ***********************************************
      // Update/modify address
      // ***********************************************
      else if(!empty($editaddr)) {

	 // Stage one: Copy data into form
         if(sizeof($sel) > 0) {
	    if(sizeof($sel) > 1) {
	       $formerror = _("You can only edit one address at the time");
	       $showaddrlist = true;
	       $defselected = $sel;
	    } else {
	       $abortform = true;
	       list($ebackend, $enick) = split(":", $sel[0]);
	       $olddata = $abook->lookup($enick, $ebackend);

	       // Display the "new address" form
	       printf("<FORM ACTION=\"%s\" METHOD=\"POST\">\n", $PHP_SELF);
	       print "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
	       print "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n<STRONG>";
	       print _("Update address");
	       print "<STRONG>\n</TD></TR>\n";
	       print "</TABLE>\n";
	       address_form("editaddr", _("Update address"), $olddata);
	       printf("<INPUT TYPE=hidden NAME=oldnick VALUE=\"%s\">\n",
		      htmlspecialchars($olddata["nickname"]));
	       printf("<INPUT TYPE=hidden NAME=backend VALUE=\"%s\">\n",
		      htmlspecialchars($olddata["backend"]));
	       print "<INPUT TYPE=hidden NAME=doedit VALUE=1>\n";
	       print "</FORM>";	       
	    }
	 }

	 // Stage two: Write new data
	 else if($doedit = 1) {
	    $newdata = $editaddr;
	    $r = $abook->modify($oldnick, $newdata, $backend);

	    // Handle error messages
	    if(!$r) {
	       // Display error
	       print "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
	       print "<TR><TD ALIGN=CENTER>\n<br><STRONG>";
	       print "<FONT COLOR=\"$color[2]\">"._("ERROR").": ".
		      $abook->error."</FONT>";
	       print "<STRONG>\n</TD></TR>\n";
	       print "</TABLE>\n";

	       // Display the "new address" form again
	       printf("<FORM ACTION=\"%s\" METHOD=\"POST\">\n", $PHP_SELF);
	       print "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
	       print "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n<STRONG>";
	       print _("Update address");
	       print "<STRONG>\n</TD></TR>\n";
	       print "</TABLE>\n";
	       address_form("editaddr", _("Update address"), $newdata);
	       printf("<INPUT TYPE=hidden NAME=oldnick VALUE=\"%s\">\n",
		      htmlspecialchars($newdata["nickname"]));
	       printf("<INPUT TYPE=hidden NAME=backend VALUE=\"%s\">\n",
		      htmlspecialchars($newdata["backend"]));
	       print "<INPUT TYPE=hidden NAME=doedit VALUE=1>\n";
	       print "</FORM>";	       

	       $abortform = true;
	    }
	 } 

	 // Should not get here...
	 else {
	    plain_error_message(_("Unknown error"), $color);
	    $abortform = true;
	 }
      } // End of edit address



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

      // Get and sort address list
      $alist = $abook->list_addr();
      usort($alist,'alistcmp');
      $prevbackend = -1;
      $headerprinted = false;

      // List addresses
      while(list($key,$row) = each($alist)) {

	 // New table header for each backend
	 if($prevbackend != $row["backend"]) {
	    if($prevbackend >= 0) {
	       print "<TR><TD COLSPAN=5 ALIGN=center>";
	       print "&nbsp;<BR></TD></TR></TABLE>\n";
	    }

	    print "<TABLE WIDTH=\"95%\" COLS=1 ALIGN=CENTER>\n";
	    print "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n<STRONG>";
	    print $row["source"];
	    print "<STRONG>\n</TD></TR>\n";
	    print "</TABLE>\n";

	    print '<TABLE COLS=5 BORDER=0 CELLPADDING=1 CELLSPACING=0 WIDTH="90%" ALIGN=center>';
	    printf('<TR BGCOLOR="%s"><TH ALIGN=left WIDTH="%s">&nbsp;'.
		   '<TH ALIGN=left WIDTH="%s">%s<TH ALIGN=left WIDTH="%s">%s'.
		   '<TH ALIGN=left WIDTH="%s">%s<TH ALIGN=left WIDTH="%s">%s'.
		   "</TR>\n", $color[9], "1%", 
		   "1%", _("Nickname"), 
		   "1%", _("Name"), 
		   "1%", _("E-mail"), 
		   "%",  _("Info"));
	    $line = 0;
	    $headerprinted = true;
	 } // End of header

	 $prevbackend = $row["backend"];

	 // Check if this user is selected
	 if(in_array($row["backend"].":".$row["nickname"], $defselected)) 
	    $selected = "CHECKED";
	 else
	    $selected = "";
      
	 // Print one row
	 printf("<TR%s>",
		(($line % 2) ? " bgcolor=\"$color[0]\"" : ""));
	 print  '<TD VALIGN=top ALIGN=center WIDTH="1%"><SMALL>';
	 printf('<INPUT TYPE=checkbox %s NAME="sel[]" VALUE="%s:%s"></SMALL></TD>', 
		$selected, $row["backend"], $row["nickname"]);
	 printf('<TD VALIGN=top NOWRAP WIDTH="%s">&nbsp;%s&nbsp;</TD>'.
		'<TD VALIGN=top NOWRAP WIDTH="%s">&nbsp;%s&nbsp;</TD>',
		"1%", $row["nickname"],
		"1%", $row["name"]);
	 printf('<TD VALIGN=top NOWRAP WIDTH="%s">&nbsp;<A HREF="compose.php?send_to=%s">%s</A>&nbsp;</TD>'."\n", 
		"1%", rawurlencode($row["email"]), $row["email"]);
	 printf('<TD VALIGN=top WIDTH="%s">&nbsp;%s&nbsp;</TD>', 
		"%", $row["label"]);
	 print "</TR>\n";
	 $line++;
      } 

      // End of list. Close table.
      if($headerprinted) {
	print "<TR><TD COLSPAN=5 ALIGN=center>\n";
	printf("<INPUT TYPE=submit NAME=editaddr VALUE=\"%s\">\n",
	       _("Edit selected"));
	printf("<INPUT TYPE=submit NAME=deladdr VALUE=\"%s\">\n",
	       _("Delete selected"));
	print "</TR></TABLE></FORM>";
      }
   } // end of addresslist


   // Display the "new address" form
   printf("<FORM ACTION=\"%s\" METHOD=\"POST\">\n", $PHP_SELF);
   print "<TABLE WIDTH=100% COLS=1 ALIGN=CENTER>\n";
   print "<TR><TD BGCOLOR=\"$color[0]\" ALIGN=CENTER>\n<STRONG>";
   printf(_("Add to %s"), $abook->localbackendname);
   print "<STRONG>\n</TD></TR>\n";
   print "</TABLE>\n";
   address_form("addaddr", _("Add address"), $defdata);
   print "</FORM>";

?>

</BODY></HTML>
