<?php
   /**
    **  addrbook_search.php
    **
    **  Handle addressbook searching in the popup window.
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
   if (!isset($addressbook_php))
      include("../functions/addressbook.php");

   // Authenticate user and load prefs
   $imapConnection = sqimap_login($username, $key, 
				  $imapServerAddress, $imapPort, 10);
   include("../src/load_prefs.php");
   sqimap_logout ($imapConnection);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>
<HEAD>
<TITLE><?php 
   printf("%s: %s", $org_title, _("Address Book")); 
?></TITLE>
</HEAD>

<?php
   // Choose correct colors for top and bottom frame
   if($show == "form") {
      echo "<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" ";
      echo "LINK=\"$color[6]\" VLINK=\"$color[6]\" ALINK=\"$color[6]\" ";
      echo "OnLoad=\"document.sform.query.focus();\">";  
   } else {
      echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" ";
      echo "LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
   }

   // Just make a blank page and exit
   if(($show == "blank") || (empty($query) && empty($show)))  {
      printf("<P ALIGN=center><BR>%s</P>\n</BODY></HTML>\n",
	     _("Search results will display here"));
      exit;
   }

   // Create search form 
   if($show == "form") {
      printf("<FORM NAME=sform TARGET=abookres ACTION=\"%s\" METHOD=GET>\n",
	     $PHP_SELF);
      printf("<TABLE BORDER=0 WIDTH=\"100%%\" HEIGHT=\"100%%\">");
      printf("<TR><TD NOWRAP VALIGN=middle>\n");
      printf("  <STRONG>%s:</STRONG>\n</TD><TD VALIGN=middle>\n",
	     _("Search for"));
      printf("  <INPUT TYPE=text NAME=query VALUE=\"%s\" SIZE=30>\n",
	     htmlspecialchars($query));
      printf("</TD><TD VALIGN=middle>\n");
      printf("  <INPUT TYPE=submit VALUE=\"%s\">",
	     _("Search"));
      printf("</TD><TD WIDTH=\"50%%\" VALIGN=middle ALIGN=right>\n");
      printf("<INPUT TYPE=button VALUE=\"%s\" onclick=\"parent.close();\">\n",
             _("Close window"));
      printf("</TD></TR></TABLE></FORM>\n");
   }

   // Include JavaScript code if this is search results
   if(!empty($query)) {
?>
<SCRIPT LANGUAGE="Javascript"><!--

function to_address($addr) {
  var prefix    = "";
  var pwintype = typeof parent.opener.document.compose;

  if(pwintype != "undefined" ) {
    if ( parent.opener.document.compose.send_to.value ) {
      prefix = ", ";
      parent.opener.document.compose.send_to.value = 
        parent.opener.document.compose.send_to.value + ", " + $addr;      
    } else {
      parent.opener.document.compose.send_to.value = $addr;
    }
  }
}

function cc_address($addr) {
  var prefix    = "";
  var pwintype = typeof parent.opener.document.compose;

  if(pwintype != "undefined" ) {
    if ( parent.opener.document.compose.send_to_cc.value ) {
      prefix = ", ";
      parent.opener.document.compose.send_to_cc.value = 
        parent.opener.document.compose.send_to_cc.value + ", " + $addr;      
    } else {
      parent.opener.document.compose.send_to_cc.value = $addr;
    }
  }
}

function bcc_address($addr) {
  var prefix    = "";
  var pwintype = typeof parent.opener.document.compose;

  if(pwintype != "undefined" ) {
    if ( parent.opener.document.compose.bcc.value ) {
      prefix = ", ";
      parent.opener.document.compose.bcc.value = 
        parent.opener.document.compose.bcc.value + ", " + $addr;      
    } else {
      parent.opener.document.compose.bcc.value = $addr;
    }
  }
}

// --></SCRIPT>

<?php 
   } // End of included JavaScript code

   // Do the search
   if(!empty($query)) {
      $abook = addressbook_init();
      $res = $abook->s_search($query);

      if(!is_array($res)) {
	 printf("<P ALIGN=center><BR>%s:<br>%s</P>\n</BODY></HTML>\n",
		_("Your search failed with the following error(s)"),
		$abook->error);
	 exit;
      }

      if(sizeof($res) == 0) {
	 printf("<P ALIGN=center><BR>%s.</P>\n</BODY></HTML>\n",
		_("No persons matching your search was found"));
	 exit;
      }

      // List search results
      $line = 0;
      print "<table border=0 width=\"98%\" align=center>";
      printf("<tr bgcolor=\"$color[9]\"><TH align=left>&nbsp;".
             "<TH align=left>&nbsp;%s<TH align=left>&nbsp;%s".
	     "<TH align=left>&nbsp;%s<TH align=left width=\"10%%\">".
	     "&nbsp;%s</tr>\n",
             _("Name"), _("E-mail"), _("Info"), _("Source"));

      while(list($key, $row) = each($res)) {
	 printf("<tr%s nowrap><td nowrap align=center width=\"5%%\">".
		"<a href=\"javascript:to_address('%s');\">To</A> | ".
		"<a href=\"javascript:cc_address('%s');\">Cc</A>".
		"<td nowrap>&nbsp;%s&nbsp;<td nowrap>&nbsp;%s&nbsp;".
		"<td nowrap>&nbsp;%s&nbsp;<td nowrap>&nbsp;%s</tr>\n", 
		($line % 2) ? " bgcolor=\"$color[0]\"" : "", $row["email"],
		$row["email"], $row["name"], $row["email"], $row["label"], 
		$row["source"]);
	 $line++;
      }
      print "</TABLE>";
   }
?>

</BODY></HTML>
