<?php

/**
 * addressbook.php
 *
 * Manage personal address book.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage addressbook
 */

/**
 * Include the SquirrelMail initialization file.
 */
include('../include/init.php');

/** SquirrelMail required files. */
/* address book functions */
require_once(SM_PATH . 'functions/addressbook.php');
include_once(SM_PATH . 'templates/util_addressbook.php');
include_once(SM_PATH . 'templates/util_global.php');

/* form functions */
require_once(SM_PATH . 'functions/forms.php');

/** lets get the global vars we may need */

/* From the address form */
sqgetGlobalVar('addaddr',       $addaddr,       SQ_POST);
sqgetGlobalVar('editaddr',      $editaddr,      SQ_POST);
sqgetGlobalVar('deladdr',       $deladdr,       SQ_POST);
sqgetGlobalVar('sel',           $sel,           SQ_POST);
sqgetGlobalVar('oldnick',       $oldnick,       SQ_POST);
sqgetGlobalVar('backend',       $backend,       SQ_POST);
sqgetGlobalVar('doedit',        $doedit,        SQ_POST);

/* Get sorting order */
$abook_sort_order = get_abook_sort();

/* Create page header before addressbook_init in order to  display error messages correctly. */
displayPageHeader($color, 'None');

/* Open addressbook with error messages on.
 remote backends (LDAP) are enabled because they can be used. (list_addr function)
*/
$abook = addressbook_init(true, false);

// FIXME: do we have to stop use of address book, when localbackend is not present.
if($abook->localbackend == 0) {
    plain_error_message(_("No personal address book is defined. Contact administrator."));
    exit();
}

$current_backend = $abook->localbackend;
if (sqgetGlobalVar('new_bnum',$new_backend,SQ_POST) && array_key_exists($new_backend,$abook->backends)) {
    $current_backend = (int) $new_backend;
}

$abook_selection = '&nbsp;';
$list_backends = array();
if (count($abook->backends) > 1) {
    foreach($abook->get_backend_list() as $oBackend) {
        if ($oBackend->listing) {
            $list_backends[$oBackend->bnum]=$oBackend->sname;
        }
    }
    if (count($list_backends)>1) {
        $abook_selection = addSelect('new_bnum',$list_backends,$current_backend,true)
            .addSubmit(_("Change"),'change_abook');
    }
}

$defdata   = array();
$formerror = '';
$abortform = false;
$showaddrlist = true;
$defselected  = array();
$form_url = 'addressbook.php';

/* Handle user's actions */
if(sqgetGlobalVar('REQUEST_METHOD', $req_method, SQ_SERVER) && $req_method == 'POST') {

    /**************************************************
     * Add new address                                *
     **************************************************/
    if (isset($addaddr)) {
        if (isset($backend)) {
            $r = $abook->add($addaddr, $backend);
        } else {
            $r = $abook->add($addaddr, $abook->localbackend);
        }

        /* Handle error messages */
        if (!$r) {
            /* Remove backend name from error string */
            $errstr = $abook->error;
            $errstr = ereg_replace('^\[.*\] *', '', $errstr);

            $formerror = $errstr;
            $showaddrlist = false;
            $defdata = $addaddr;
        }
    } else {

        /************************************************
         * Delete address(es)                           *
         ************************************************/
        if ((!empty($deladdr)) && sizeof($sel) > 0) {
            $orig_sel = $sel;
            sort($sel);

            /* The selected addresses are identidied by "nickname_backend". *
             * Sort the list and process one backend at the time            */
            $prevback  = -1;
            $subsel    = array();
            $delfailed = false;

            for ($i = 0 ; (($i < sizeof($sel)) && !$delfailed) ; $i++) {
                list($snick, $sbackend) = explode('_', $sel[$i]);

                /* When we get to a new backend, process addresses in *
                 * previous one.                                      */
                if ($prevback != $sbackend && $prevback != -1) {

                    $r = $abook->remove($subsel, $prevback);
                    if (!$r) {
                        $formerror = $abook->error;
                        $i = sizeof($sel);
                        $delfailed = true;
                        break;
                    }
                    $subsel   = array();
                }

                /* Queue for processing */
                array_push($subsel, $snick);
                $prevback = $sbackend;
            }

            if (!$delfailed) {
                $r = $abook->remove($subsel, $prevback);
                if (!$r) { /* Handle errors */
                    $formerror = $abook->error;
                    $delfailed = true;
                }
            }

            if ($delfailed) {
                $showaddrlist = true;
                $defselected  = $orig_sel;
            }

        } else {

            /***********************************************
             * Update/modify address                       *
             ***********************************************/
            if (!empty($editaddr)) {
                /* Stage one: Copy data into form */
                if (isset($sel) && sizeof($sel) > 0) {
                    if(sizeof($sel) > 1) {
                        $formerror = _("You can only edit one address at the time");
                        $showaddrlist = true;
                        $defselected = $sel;
                    } else {
                        $abortform = true;
                        list($enick, $ebackend) = explode('_', current($sel));
                        $olddata = $abook->lookup($enick, $ebackend);
                        // Test if $olddata really contains anything and return an error message if it doesn't
                        if (!$olddata) {
                            error_box(nl2br(htmlspecialchars($abook->error)));
                        } else {
                            /* Display the "new address" form */
                            abook_create_form($form_url,'editaddr',_("Update address"),_("Update address"),$olddata);
                            echo addHidden('oldnick', $olddata['nickname']).
                                addHidden('backend', $olddata['backend']).
                                addHidden('doedit', '1').
                                '</form>';
                        }
                    }
                } elseif ($doedit == 1) {
                    /* Stage two: Write new data */
                    $newdata = $editaddr;
                    $r = $abook->modify($oldnick, $newdata, $backend);

                    /* Handle error messages */
                    if (!$r) {
                        /* Display error */
                        plain_error_message( nl2br(htmlspecialchars($abook->error)));

                        /* Display the "new address" form again */
                        abook_create_form($form_url,'editaddr',_("Update address"),_("Update address"),$newdata);
                        echo addHidden('oldnick', $oldnick).
                            addHidden('backend', $backend).
                            addHidden('doedit',  '1').
                            "\n" . '</form>';
                        $abortform = true;
                    }
                } else {
                    /**
                     * $editaddr is set, but $sel (address selection in address listing)
                     * and $doedit (address edit form) are not set.
                     * Assume that user clicked on "Edit address" without selecting any address.
                     */
                    $formerror = _("Please select address that you want to edit");
                    $showaddrlist = true;
                } /* end of edit stage detection */
            } /* !empty($editaddr)                  - Update/modify address */
        } /* (!empty($deladdr)) && sizeof($sel) > 0 - Delete address(es) */
    } /* !empty($addaddr['nickname'])               - Add new address */

    // Some times we end output before forms are printed
    if($abortform) {
        echo "</body></html>\n";
        exit();
    }
}


/* =================================================================== *
 * The following is only executed on a GET request, or on a POST when  *
 * a user is added, or when "delete" or "modify" was successful.       *
 * =================================================================== */

/* Display error messages */
if (!empty($formerror)) {
    plain_error_message(nl2br(htmlspecialchars($formerror)));
}


/* Display the address management part */
$addresses = array();
while (list($k, $backend) = each ($abook->backends)) {
    $a = array();
    $a['BackendID'] = $backend->bnum;
    $a['BackendSource'] = $backend->sname;
    $a['BackendWritable'] = $backend->writeable;
    $a['Addresses'] = array();

    $alist = $abook->list_addr($backend->bnum);

    /* check return (array with data or boolean false) */
    if (is_array($alist)) {
        usort($alist,'alistcmp');

        $a['Addresses'] = formatAddressList($alist);
  
        $addresses[$backend->bnum] = $a;
    } else {
        // list_addr() returns boolean
        plain_error_message(nl2br(htmlspecialchars($abook->error)));
    }
}


if ($showaddrlist) {
    echo addForm($form_url, 'post');
    
    $oTemplate->assign('addresses', $addresses);
    $oTemplate->assign('current_backend', $current_backend);
    $oTemplate->assign('backends', $list_backends);
    $oTemplate->assign('abook_has_extra_field', $abook->add_extra_field);
        
    $oTemplate->display('addressbook_list.tpl');
    
//FIXME: Remove HTML from here!
    echo "</form>\n";
}

/* Display the "new address" form */
//FIXME: Remove HTML from here!
echo '<a name="AddAddress"></a>' . "\n";
abook_create_form($form_url,'addaddr',_("Add to address book"),_("Add address"),$defdata);
echo "</form>\n";

/* Hook for extra address book blocks */
do_hook('addressbook_bottom', $null);

$oTemplate->display('footer.tpl');
?>
