<?php
/**
 * util_addressbook.php
 *
 * Functions to make working with address books easier
 * 
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

//FIXME: the functions in this file should be reviewed and moved to functions/template/abook_util.php and this file should be removed
/**
 * Create a link to compose an email to the email address given.
 * 
 * @param array $row contact as given to the addressbook_list.tpl template
 * @author Steve Brown
 * @since 1.5.2
 */
function composeLink ($row) {
    return makeComposeLink('src/compose.php?send_to=' .
                           rawurlencode($row['RawFullAddress']),
                           sm_encode_html_special_chars($row['Email']));
}

/**
 * Format the address book into a format that is easy for template authors
 * to use
 * 
 * @param array $addresses all contacts as given by calling $abook->list_addr()
 * @return array
 * @author Steve Brown
 * @since 1.5.2
 */
function formatAddressList ($addresses) {
    if (!is_array($addresses) || count($addresses) == 0)
        return array();
        
    $contacts = array();
    while(list($undef,$row) = each($addresses)) {
        $contact = array (
                            'FirstName'      => sm_encode_html_special_chars($row['firstname']),
                            'LastName'       => sm_encode_html_special_chars($row['lastname']),
                            'FullName'       => sm_encode_html_special_chars($row['name']),
                            'NickName'       => sm_encode_html_special_chars($row['nickname']),
                            'Email'          => sm_encode_html_special_chars($row['email']),
                            'FullAddress'    => sm_encode_html_special_chars(AddressBook::full_address($row)),
                            'RawFullAddress' => AddressBook::full_address($row),
                            'Info'           => sm_encode_html_special_chars($row['label']),
                            'Extra'          => (isset($row['extra']) ? $row['extra'] : NULL),
                            'Source'         => sm_encode_html_special_chars($row['source']),
                            'JSEmail'        => sm_encode_html_special_chars(addcslashes(AddressBook::full_address($row), "'"), ENT_QUOTES),
                         );
        $contacts[] = $contact;
    }
    
    return $contacts;
}

/**
 * Function to include JavaScript code
 * @return void
 */
function insert_javascript() {
    ?>
    <script type="text/javascript"><!--

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

    function CheckAll(ch) {
        var chkObj = "";
        for (var i = 0; i < document.addressbook.elements.length; i++) {
            chkObj = document.addressbook.elements[i];
            if (chkObj.type == "checkbox" && chkObj.name.substr(0,16) == "send_to_search[" + ch) {
                chkObj.checked = !(chkObj.checked);
            }
        }
    }

// --></script>
<?php
} /* End of included JavaScript */

/**
 * Function to build a list of available backends for searching
 * 
 * @return array
 * @author Steve Brown
 * @since 1.5.2
 */
function getBackends () {
    global $abook;
    
    $backends = array();
    $backends['-1'] = _("All address books");
    $ret = $abook->get_backend_list();
    while (list($undef,$v) = each($ret)) {
        if ($v->btype == 'local' && !$v->listing) {
            continue;
        }
        $backends[$v->bnum] = $v->sname;
    }
    
    return $backends;
}
