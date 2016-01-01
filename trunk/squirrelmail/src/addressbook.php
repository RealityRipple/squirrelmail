<?php

/**
 * addressbook.php
 *
 * Manage personal address book.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage addressbook
 */

/** This is the addressbook page */
define('PAGE_NAME', 'addressbook');

/**
 * Include the SquirrelMail initialization file.
 */
include('../include/init.php');

/** SquirrelMail required files. */
/* address book functions */
require_once(SM_PATH . 'functions/addressbook.php');
include_once(SM_PATH . 'templates/util_addressbook.php');

/* form functions */
require_once(SM_PATH . 'functions/forms.php');

/** lets get the global vars we may need */

/* From the address form */
sqgetGlobalVar('smtoken',       $submitted_token, SQ_POST, '');
sqgetGlobalVar('addaddr',       $addaddr,       SQ_POST);
sqgetGlobalVar('editaddr',      $editaddr,      SQ_POST);
sqgetGlobalVar('deladdr',       $deladdr,       SQ_POST);
sqgetGlobalVar('compose_to',    $compose_to,    SQ_POST);
sqgetGlobalVar('sel',           $sel,           SQ_POST);
sqgetGlobalVar('oldnick',       $oldnick,       SQ_POST);
sqgetGlobalVar('backend',       $backend,       SQ_POST);
sqgetGlobalVar('doedit',        $doedit,        SQ_POST);
$page_size = $abook_show_num;
if (!sqGetGlobalVar('page_number', $page_number, SQ_FORM))
    if (!sqGetGlobalVar('current_page_number', $page_number, SQ_FORM))
        $page_number = 1;
if (!sqGetGlobalVar('show_all', $show_all, SQ_FORM))
    $show_all = 0;

/* Get sorting order */
$abook_sort_order = get_abook_sort();

// Create page header before addressbook_init in order to
// display error messages correctly, unless we might be
// redirecting the browser to the compose page.
//
if ((empty($compose_to)) || sizeof($sel) < 1)
    displayPageHeader($color);

/* Open addressbook with error messages on.
 remote backends (LDAP) are enabled because they can be used. (list_addr function)
*/
$abook = addressbook_init(true, false);

// FIXME: do we really have to stop use of address book when localbackend is not present?
if($abook->localbackend == 0) {
    plain_error_message(_("No personal address book is defined. Contact administrator."));
    exit();
}

$current_backend = $abook->localbackend;
if (sqgetGlobalVar('new_bnum', $new_backend, SQ_FORM)
 && array_key_exists($new_backend, $abook->backends)) {
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

    // first, validate security token
    sm_validate_security_token($submitted_token, -1, TRUE);

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
            $errstr = preg_replace('/^\[.*\] */', '', $errstr);

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

            /* The selected addresses are identified by "backend_nickname". *
             * Sort the list and process one backend at the time            */
            $prevback  = -1;
            $subsel    = array();
            $delfailed = false;

            for ($i = 0 ; (($i < sizeof($sel)) && !$delfailed) ; $i++) {
                list($sbackend, $snick) = explode('_', $sel[$i], 2);

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

        /************************************************
         * Compose to selected address(es)              *
         ************************************************/
        } else if ((!empty($compose_to)) && sizeof($sel) > 0) {
            $orig_sel = $sel;
            sort($sel);

            // The selected addresses are identified by "backend_nickname"
            $lookup_failed = false;
            $send_to = '';

            for ($i = 0 ; (($i < sizeof($sel)) && !$lookup_failed) ; $i++) {
                list($sbackend, $snick) = explode('_', $sel[$i], 2);

                $data = $abook->lookup($snick, $sbackend);

                if (!$data) {
                    $formerror = $abook->error;
                    $lookup_failed = true;
                    break;
                } else {
                    $addr = $abook->full_address($data);
                    if (!empty($addr))
                        $send_to .= $addr . ', ';
                }
            }


            if ($lookup_failed || empty($send_to)) {
                $showaddrlist = true;
                $defselected  = $sel;

                // we skipped the page header above for this functionality, so add it here
                displayPageHeader($color);
            }


            // send off to compose screen
            else {
                $send_to = trim($send_to, ', ');
                header('Location: ' . $base_uri . 'src/compose.php?send_to=' . rawurlencode($send_to));
                exit;
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
                        list($ebackend, $enick) = explode('_', current($sel), 2);
                        $olddata = $abook->lookup($enick, $ebackend);
                        // Test if $olddata really contains anything and return an error message if it doesn't
                        if (!$olddata) {
                            error_box(nl2br(sm_encode_html_special_chars($abook->error)));
                        } else {
                            /* Display the "new address" form */
                            echo abook_create_form($form_url, 'editaddr',
                                                   _("Update address"),
                                                   _("Update address"),
                                                   $current_backend,
                                                   $olddata);
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
                        plain_error_message( nl2br(sm_encode_html_special_chars($abook->error)));

                        /* Display the "new address" form again */
                        echo abook_create_form($form_url, 'editaddr',
                                               _("Update address"),
                                               _("Update address"),
                                               $current_backend,
                                               $newdata);
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
            } /* !empty($editaddr)                     - Update/modify address */
        } /* (!empty($deladdr)) && sizeof($sel) > 0    - Delete address(es) 
          or (!empty($compose_to)) && sizeof($sel) > 0 - Compose to address(es) */
    } /* !empty($addaddr['nickname'])                  - Add new address */

    // Some times we end output before forms are printed
    if($abortform) {
//FIXME: use footer.tpl; remove HTML from core
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
    plain_error_message(nl2br(sm_encode_html_special_chars($formerror)));
}


/* Display the address management part */
$addresses = array();
while (list($k, $backend) = each ($abook->backends)) {
    $a = array();
    $a['BackendID'] = $backend->bnum;
    $a['BackendSource'] = $backend->sname;
    $a['BackendWritable'] = $backend->writeable;
    $a['Addresses'] = array();

    // don't do address lookup if we are not viewing that backend
    //
    if ($backend->bnum == $current_backend) {
        $alist = $abook->list_addr($backend->bnum);

        /* check return (array with data or boolean false) */
        if (is_array($alist)) {
            usort($alist,'alistcmp');
    
            $a['Addresses'] = formatAddressList($alist);
  
            $addresses[$backend->bnum] = $a;
        } else {
            // list_addr() returns boolean
            plain_error_message(nl2br(sm_encode_html_special_chars($abook->error)));
        }
    } else {
        $addresses[$backend->bnum] = $a;
    }
}


$current_page_args = array(
                           'abook_sort_order' => $abook_sort_order,
                           'new_bnum'         => $current_backend,
                           'page_number'      => $page_number,
                          );


// note that plugins can add to $current_page_args as well as
// filter the address list
//
$temp = array(&$addresses, &$current_backend, &$page_number, &$current_page_args);
do_hook('abook_list_filter', $temp);


// NOTE to address book backend authors and plugin authors: if a backend does
//      pagination (which might be more efficient), it needs to place a key
//      in every address listing it returns called "paginated", whose value
//      should evaluate to boolean TRUE.  However, if a plugin will also be
//      used on the hook above to filter the addresses (perhaps by group), then
//      the backend should be made compatible with the filtering plugin and
//      should do the actual filtering too.  Otherwise, the backend will paginate
//      before filtering has taken place, the output of which is clearly wrong.
//      It is proposed that filtering be based on a GET/POST variable called
//      "abook_groups_X" where X is the current backend number.  The value of
//      this varaible would be an array of possible filter names, which the
//      plugin and the backend would both know about.  The plugin would only
//      filter based on that value if the backend didn't already do it.  The
//      backend can insert a "grouped" key into all address listings, whose
//      value evaluates to boolean TRUE, telling the plugin not to do any
//      filtering itself.  For an example of this implementation, see the
//      Address Book Grouping and Pagination plugin.


// if no pagination was done by a plugin or the abook
// backend (which is indicated by the presence of a
// "paginated" key within all of the address entries
// in the list of addresses for the backend currently
// being viewed), then we provide default pagination
//
$total_addresses = 0;
if (!$show_all
 && is_array($addresses[$current_backend]['Addresses'])
 && empty($addresses[$current_backend]['Addresses'][0]['paginated'])) {

    // at this point, we assume the current list is
    // the *full* list
    //
    $total_addresses = sizeof($addresses[$current_backend]['Addresses']);

    // iterate through all the entries, building list of addresses
    // to keep based on current page
    //
    $new_address_list = array();
    $total_pages = ceil($total_addresses / $page_size);
    if ($page_number > $total_pages) $page_number = $total_pages;
    $page_count = 1;
    $page_item_count = 0;
    foreach ($addresses[$current_backend]['Addresses'] as $addr) {
        $page_item_count++;
        if ($page_item_count > $page_size) {
            $page_count++;
            $page_item_count = 1;
        }
        if ($page_count == $page_number)
            $new_address_list[] = $addr;
    }
    $addresses[$current_backend]['Addresses'] = $new_address_list;

}


if ($showaddrlist) {
    
    $oTemplate->assign('show_all', $show_all);
    $oTemplate->assign('page_number', $page_number);
    $oTemplate->assign('page_size', $page_size);
    $oTemplate->assign('total_addresses', $total_addresses);
    $oTemplate->assign('abook_compact_paginator', $abook_compact_paginator);
    $oTemplate->assign('abook_page_selector', $abook_page_selector);
    $oTemplate->assign('current_page_args', $current_page_args);
    $oTemplate->assign('abook_page_selector_max', $abook_page_selector_max);
    $oTemplate->assign('addresses', $addresses);
    $oTemplate->assign('current_backend', $current_backend);
    $oTemplate->assign('backends', $list_backends);
    $oTemplate->assign('abook_has_extra_field', $abook->add_extra_field);
    $oTemplate->assign('compose_new_win', $compose_new_win);
    $oTemplate->assign('compose_height', $compose_height);
    $oTemplate->assign('compose_width', $compose_width);
    $oTemplate->assign('form_action', $form_url);
        
    $oTemplate->display('addressbook_list.tpl');
    
}

/* Display the "new address" form */
//FIXME: Remove HTML from here! (echo abook_create_form() is OK, since it is all template based output
echo '<a name="AddAddress"></a>' . "\n";
echo abook_create_form($form_url, 'addaddr',
                       _("Add to address book"),
                       _("Add address"),
                       $current_backend,
                       $defdata);
echo "</form>\n";

/* Hook for extra address book blocks */
do_hook('addressbook_bottom', $null);

$oTemplate->display('footer.tpl');
