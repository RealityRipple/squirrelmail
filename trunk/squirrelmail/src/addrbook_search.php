<?php
   /**
    **  addrbook_search.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Handle addressbook searching in the popup window.
    **
    **  NOTE: A lot of this code is similar to the code in
    **        addrbook_search_html.html -- If you change one,
    **        change the other one too!
    **
    **  $Id$
    **/

   require_once('../src/validate.php');

   // Function to include JavaScript code
   function insert_javascript() {
?>
<SCRIPT LANGUAGE="Javascript"><!--

function to_and_close($addr) {
  to_address($addr);
  parent.close();
}

function to_address($addr) {
  var prefix    = "";
  var pwintype = typeof parent.opener.document.compose;

  $addr = $addr.replace(/ {1,35}$/, "");

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

  $addr = $addr.replace(/ {1,35}$/, "");

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

  $addr = $addr.replace(/ {1,35}$/, "");

  if(pwintype != "undefined" ) {
    if ( parent.opener.document.compose.send_to_bcc.value ) {
      prefix = ", ";
      parent.opener.document.compose.send_to_bcc.value =
        parent.opener.document.compose.send_to_bcc.value + ", " + $addr;
    } else {
      parent.opener.document.compose.send_to_bcc.value = $addr;
    }
  }
}

// --></SCRIPT>

<?php
   } // End of included JavaScript


    // List search results
    function display_result($res, $includesource = true) {
        global $color;
        
        if(sizeof($res) <= 0) return;
        
        insert_javascript();
        
        $line = 0;
        echo '<TABLE BORDER="0" WIDTH="98%" ALIGN=center>';
        printf("<TR BGCOLOR=\"$color[9]\"><TH ALIGN=left>&nbsp;".
            "<TH ALIGN=left>&nbsp;%s<TH ALIGN=left>&nbsp;%s".
            "<TH ALIGN=left>&nbsp;%s",
            _("Name"), _("E-mail"), _("Info"));
        
        if($includesource)
            printf("<TH ALIGN=left WIDTH=\"10%%\">&nbsp;%s", _("Source"));
    
        echo "</TR>\n";
    
        while(list($undef, $row) = each($res)) {
            printf("<tr%s nowrap><td valign=top nowrap align=center width=\"5%%\">".
                    "<small><a href=\"javascript:to_address('%s');\">To</A> | ".
                    "<a href=\"javascript:cc_address('%s');\">Cc</A> | ".
                    "<a href=\"javascript:bcc_address('%s');\">Bcc</A></small>".
                    "<td nowrap valign=top>&nbsp;%s&nbsp;<td nowrap valign=top>".
                    "&nbsp;<a href=\"javascript:to_and_close('%s');\">%s</A>&nbsp;".
                    "<td valign=top>&nbsp;%s&nbsp;",
                    ($line % 2) ? " bgcolor=\"$color[0]\"" : "",
                    $row["email"], $row["email"], $row["email"],
                    $row["name"],  $row["email"], $row["email"],
                    $row["label"]);
    
            if($includesource)
                printf("<td nowrap valign=top>&nbsp;%s", $row["source"]);
    
            echo "</TR>\n";
            $line++;
        }
        echo '</TABLE>';
    }

    /* ================= End of functions ================= */
    
    require_once('../functions/array.php');
    require_once('../functions/strings.php');
    require_once('../functions/addressbook.php');
    
    displayHtmlHeader();
    
    // Initialize vars
    if(!isset($query)) $query = "";
    if(!isset($show))  $show  = "";

    // Choose correct colors for top and bottom frame
    if($show == 'form') {
        echo "<BODY BGCOLOR=\"$color[3]\" TEXT=\"$color[6]\" ";
        echo "LINK=\"$color[6]\" VLINK=\"$color[6]\" ALINK=\"$color[6]\" ";
        echo 'OnLoad="document.sform.query.focus();">';
    } else {
        echo "<BODY TEXT=\"$color[8]\" BGCOLOR=\"$color[4]\" ";
        echo "LINK=\"$color[7]\" VLINK=\"$color[7]\" ALINK=\"$color[7]\">\n";
    }

    // Empty search
    if(empty($query) && empty($show) && empty($listall))  {
        printf("<P ALIGN=center><BR>%s</P>\n</BODY></HTML>\n",
         _("No persons matching your search was found"));
      exit;
    }

    // Initialize addressbook
    $abook = addressbook_init();

    // Create search form
    if($show == 'form') {
        echo "<FORM NAME=sform TARGET=abookres ACTION=\"$PHP_SELF\" METHOD=\"POST\">\n";
        echo '<TABLE BORDER="0" WIDTH="100%" HEIGHT="100%">';
        echo "<TR><TD NOWRAP VALIGN=middle>\n";
        printf("  <STRONG>%s</STRONG>\n", _("Search for"));
        printf("  <INPUT TYPE=text NAME=query VALUE=\"%s\" SIZE=26>\n",
         htmlspecialchars($query));
        
        // List all backends to allow the user to choose where to search
        if($abook->numbackends > 1) {
            printf("<STRONG>%s</STRONG>&nbsp;<SELECT NAME=backend>\n",
               _("in"));
            printf("<OPTION VALUE=-1 SELECTED>%s\n",
               _("All address books"));
            $ret = $abook->get_backend_list();
            while(list($undef,$v) = each($ret))
                printf("<OPTION VALUE=%d>%s\n", $v->bnum, $v->sname);
            print "</SELECT>\n";
        } else {
            print "<INPUT TYPE=hidden NAME=backend VALUE=-1>\n";
        }
        
        printf("<INPUT TYPE=submit VALUE=\"%s\">",
             _("Search"));
        printf("&nbsp;|&nbsp;<INPUT TYPE=submit VALUE=\"%s\" NAME=listall>\n",
             _("List all"));
        print "</TD><TD ALIGN=right>\n";
        printf("<INPUT TYPE=button VALUE=\"%s\" onclick=\"parent.close();\">\n",
             _("Close window"));
        print "</TD></TR></TABLE></FORM>\n";
    } else

    // Show personal addressbook
    if($show == 'blank' || !empty($listall)) {

        if($backend != -1 || $show == 'blank') {
            if($show == 'blank')
                $backend = $abook->localbackend;

            $res = $abook->list_addr($backend);

            if(is_array($res)) {
                display_result($res, false);
            } else {
                printf("<P ALIGN=center><STRONG>"._("Unable to list addresses from %s").
                       "</STRONG></P>\n", $abook->backends[$backend]->sname);
            }

        } else {
         $res = $abook->list_addr();
         display_result($res, true);
        }

    } else

    // Do the search
    if(!empty($query) && empty($listall)) {
    
        if($backend == -1) {
            $res = $abook->s_search($query);
        } else {
            $res = $abook->s_search($query, $backend);
        }
        
        if(!is_array($res)) {
            printf("<P ALIGN=center><B><BR>%s:<br>%s</B></P>\n</BODY></HTML>\n",
                   _("Your search failed with the following error(s)"),
            $abook->error);
            exit;
        }
        
        if(sizeof($res) == 0) {
            printf("<P ALIGN=center><BR><B>%s.</B></P>\n</BODY></HTML>\n",
                   _("No persons matching your search was found"));
            exit;
        }
        
        display_result($res);
    }
   
   echo "</BODY></HTML>\n";
   
?>