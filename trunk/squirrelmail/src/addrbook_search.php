<?php

/**
 * addrbook_search.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Handle addressbook searching in the popup window.
 *
 * NOTE: A lot of this code is similar to the code in
 *       addrbook_search_html.html -- If you change one,
 *       change the other one too!
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/html.php');

/* Function to include JavaScript code */
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

        if (pwintype != "undefined") {
            if (parent.opener.document.compose.send_to.value) {
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

        if (pwintype != "undefined") {
            if (parent.opener.document.compose.send_to_cc.value) {
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

        if (pwintype != "undefined") {
            if (parent.opener.document.compose.send_to_bcc.value) {
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
} /* End of included JavaScript */


/* List search results */
function display_result($res, $includesource = true) {
    global $color;
        
    if(sizeof($res) <= 0) return;
        
    insert_javascript();
        
    $line = 0;
    echo html_tag( 'table', '', 'center', '', 'border="0" width="98%"' ) .
    html_tag( 'tr', '', '', $color[9] ) .
    html_tag( 'th', '&nbsp;', 'left' ) .
    html_tag( 'th', '&nbsp;' . _("Name"), 'left' ) .
    html_tag( 'th', '&nbsp;' . _("E-mail"), 'left' ) .
    html_tag( 'th', '&nbsp;' . _("Info"), 'left' );

    if ($includesource) {
        echo html_tag( 'th', '&nbsp;' . _("Source"), 'left', 'width="10%"' );
    }    
    echo "</tr>\n";
    
    while (list($undef, $row) = each($res)) {
        $tr_bgcolor = '';
        $email = htmlspecialchars(addcslashes(AddressBook::full_address($row), "'"), ENT_QUOTES);
        if ($line % 2) { $tr_bgcolor = $color[0]; }
        echo html_tag( 'tr', '', '', $tr_bgcolor, 'nowrap' ) .
        html_tag( 'td',
             '<small><a href="javascript:to_address(' . 
                                       "'" . $email . "');\">To</A> | " .
             '<a href="javascript:cc_address(' . 
                                       "'" . $email . "');\">Cc</A> | " .
             '<a href="javascript:bcc_address(' . 
                                 "'" . $email . "');\">Bcc</A></small>",
        'center', '', 'valign="top" width="5%" nowrap' ) .
        html_tag( 'td', '&nbsp;' . htmlspecialchars($row['name']), 'left', '', 'valign="top" nowrap' ) .
        html_tag( 'td', '&nbsp;' .
             '<a href="javascript:to_and_close(' .
                 "'" . $email . "');\">" . htmlspecialchars($row['email']) . '</A>'
        , 'left', '', 'valign="top"' ) .
        html_tag( 'td', htmlspecialchars($row['label']), 'left', '', 'valign="top" nowrap' );
        if ($includesource) {
            echo html_tag( 'td', '&nbsp;' . $row['source'], 'left', '', 'valign="top" nowrap' );
        }

        echo "</tr>\n";
        $line++;
    }
    echo '</table>';
}

/* ================= End of functions ================= */
    
require_once('../functions/array.php');
require_once('../functions/strings.php');
require_once('../functions/addressbook.php');
    
displayHtmlHeader();
    
/* Initialize vars */
if (!isset($query)) { $query = ''; }
if (!isset($show))  { $show  = ''; }
if (!isset($backend)) { $backend = ''; }

/* Choose correct colors for top and bottom frame */
if ($show == 'form' && !isset($listall)) {
    echo '<BODY TEXT="' . $color[6] . '" BGCOLOR="' . $color[3] . '" ' .
               'LINK="' . $color[6] . '" VLINK="'   . $color[6] . '" ' .
                                        'ALINK="'   . $color[6] . '" ' .
         'OnLoad="document.sform.query.focus();">';
} else {
    echo '<BODY TEXT="' . $color[8] . '" BGCOLOR="' . $color[4] . '" ' .
               'LINK="' . $color[7] . '" VLINK="'   . $color[7] . '" ' .
                                        'ALINK="'   . $color[7] . "\">\n";
}

/* Empty search */
if (empty($query) && empty($show) && empty($listall)) {
    echo html_tag( 'p', '<br>' .
                      _("No persons matching your search was found"),
            'center' ) .
          "\n</BODY></HTML>\n",
    exit;
}

/* Initialize addressbook */
$abook = addressbook_init();

/* Create search form */
if ($show == 'form' && empty($listall)) {
    echo '<FORM NAME=sform TARGET=abookres ACTION="addrbook_search.php'. 
         '" METHOD="POST">' . "\n" .
         html_tag( 'table', '', '', '', 'border="0" width="100%" height="100%"' ) .
         html_tag( 'tr' ) .
         html_tag( 'td', '  <strong>' . _("Search for") . "</strong>\n", 'left', '', 'nowrap valign="middle" width="10%"' ) .
         html_tag( 'td', '', 'left', '', '' ) .
                 '<INPUT TYPE=text NAME=query VALUE="' . htmlspecialchars($query) .
                 "\" SIZE=28>\n";

    /* List all backends to allow the user to choose where to search */
    if ($abook->numbackends > 1) {
        echo '<STRONG>' . _("in") . '</STRONG>&nbsp;<SELECT NAME=backend>'."\n".
             '<OPTION VALUE=-1 SELECTED>' . _("All address books") . "\n";
        $ret = $abook->get_backend_list();
        while (list($undef,$v) = each($ret)) {
            echo '<OPTION VALUE=' . $v->bnum . '>' . $v->sname . "\n";
        }
        echo "</SELECT>\n";
    } else {
        echo '<INPUT TYPE=hidden NAME=backend VALUE=-1>' . "\n";
    }
        
    echo '</td></tr>' .
    html_tag( 'tr',
                    html_tag( 'td', '', 'left' ) .
                    html_tag( 'td',
                            '<INPUT TYPE=submit VALUE="' . _("Search") . '" NAME=show>' .
                            '&nbsp;|&nbsp;<INPUT TYPE=submit VALUE="' . _("List all") .
                            '" NAME=listall>' . "\n" .
                            '&nbsp;|&nbsp;<INPUT TYPE=button VALUE="' . _("Close") .
                            '" onclick="parent.close();">' . "\n" ,
                    'left' )
            ) .
         '</table></form>' . "\n";
} else {

    /* Show personal addressbook */
    if ($show == 'blank' && empty($listall)) {

        if($backend != -1 || $show == 'blank') {
            if ($show == 'blank') {
                $backend = $abook->localbackend;
            }
            $res = $abook->list_addr($backend);

            if(is_array($res)) {
                usort($res,'alistcmp');
                display_result($res, false);
            } else {
                echo html_tag( 'p', '<strong>' .
                                 sprintf(_("Unable to list addresses from %s"),
                                     $abook->backends[$backend]->sname) . '</strong>' ,
                       'center' ) . "\n";
            }
        } else {
            $res = $abook->list_addr();
            usort($res,'alistcmp');
            display_result($res, true);
        }

    } else {
        if( !empty( $listall ) ){
          $query = '*';
        }

        /* Do the search */
        if (!empty($query)) {
    
            if($backend == -1) {
                $res = $abook->s_search($query);
            } else {
                $res = $abook->s_search($query, $backend);
            }
        
            if (!is_array($res)) {
                echo html_tag( 'p', '<b><br>' .
                                 _("Your search failed with the following error(s)") .
                                 ':<br>' . $abook->error . "</b>\n" ,
                       'center' ) .
                "\n</BODY></HTML>\n";
                exit;
            }
        
            if (sizeof($res) == 0) {
                echo html_tag( 'p', '<br><b>' .
                                 _("No persons matching your search was found") . "</b>\n" ,
                       'center' ) .
                "\n</BODY></HTML>\n";
                exit;
            }
        
            display_result($res);
        }
    }
   
}

echo "</BODY></HTML>\n";
   
?>
