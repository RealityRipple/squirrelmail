<?php
/**
 * addrbook_search_html.php
 *
 * Handle addressbook searching with pure html.
 *
 * This file is included from compose.php
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage addressbook
 */

/**
 * Include the SquirrelMail initialization file.
 * Because this file can also be included within compose we check for the $bInit
 * var which is set inside ini.php. It's needed because compose already includes
 * init.php.
 */
if (!isset($bInit)) {
    /** This is the addrbook_search_html page */
    define('PAGE_NAME', 'addrbook_search_html');

    include('../include/init.php');
}

/** SquirrelMail required files. */
include_once(SM_PATH . 'functions/date.php');
include_once(SM_PATH . 'functions/addressbook.php');
include_once(SM_PATH . 'templates/util_addressbook.php');

sqgetGlobalVar('session',   $session,   SQ_POST);
sqgetGlobalVar('mailbox',   $mailbox,   SQ_POST);
if (! sqgetGlobalVar('query', $addrquery, SQ_POST))
     $addrquery='';
sqgetGlobalVar('listall',   $listall,   SQ_POST);
sqgetGlobalVar('backend',   $backend,   SQ_POST);

/**
 * Insert hidden data
 */
function addr_insert_hidden() {
    global $body, $subject, $send_to, $send_to_cc, $send_to_bcc, $mailbox, $mailprio,
           $request_mdn, $request_dr, $identity, $session, $composeMessage, $action;

//FIXME Do not echo HTML from the core.  This file already uses templates mostly, so why are we echoing here at all?!?
   if (substr($body, 0, 1) == "\r") {
       echo addHidden('body', "\n".$body);
   } else {
       echo addHidden('body', $body);
   }

   if (is_object($composeMessage) && $composeMessage->entities)
       echo addHidden('attachments', urlencode(serialize($composeMessage->entities)));

   echo addHidden('session', $session).
        addHidden('smaction', $action).
        addHidden('subject', $subject).
        addHidden('send_to', $send_to).
        addHidden('send_to_bcc', $send_to_bcc).
        addHidden('send_to_cc', $send_to_cc).
        addHidden('mailprio', $mailprio).
        addHidden('request_mdn', $request_mdn).
        addHidden('request_dr', $request_dr).
        addHidden('identity', $identity).
        addHidden('mailbox', $mailbox).
        addHidden('from_htmladdr_search', 'true');
}


/**
 * List search results
 * @param array $res Array containing results of search
 * @param bool $includesource If true, adds backend column to address listing
 */
function addr_display_result($res, $includesource = true) {
    global $PHP_SELF, $oTemplate, $oErrorHandler;
    

//FIXME: no HTML output from core
    echo addForm($PHP_SELF, 'post', 'addressbook', '', '', array(), TRUE).
         addHidden('html_addr_search_done', 'true');
    addr_insert_hidden();
    
    $oTemplate->assign('compose_addr_pop', false);
    $oTemplate->assign('include_abook_name', $includesource);
    $oTemplate->assign('addresses', formatAddressList($res));
    
    $oTemplate->display('addrbook_search_list.tpl');
    
    echo '</form>';
}

/* --- End functions --- */

if ($compose_new_win == '1') {
    compose_Header($color, $mailbox);
}
else {
    displayPageHeader($color, $mailbox);
}

/** set correct value of $default_charset */
set_my_charset();

/* Initialize addressbook */
$abook = addressbook_init();


/* Search form */
echo addForm($PHP_SELF.'?html_addr_search=true', 'post', 'f');
addr_insert_hidden();
if (isset($session)) {
    echo addHidden('session', $session);
}

$oTemplate->assign('compose_addr_pop', false);
$oTemplate->assign('backends', getBackends());

$oTemplate->display('addressbook_search_form.tpl');

echo "</form>\n";
do_hook('addrbook_html_search_below', $null);
/* End search form */

/* List addresses. Show personal addressbook */
if ($addrquery == '' || ! empty($listall)) {
    // TODO: recheck all conditions and simplity if statements
    if (! isset($backend) || $backend != -1 || $addrquery == '') {
        if ($addrquery == '' && empty($listall)) {
            $backend = $abook->localbackend;
        }

        $res = $abook->list_addr($backend);

        if (is_array($res)) {
            usort($res,'alistcmp');
            addr_display_result($res, false);
        } else {
            plain_error_message(_("Unable to list addresses from %s"), $abook->backends[$backend]->sname);
        }

    } else {
        $res = $abook->list_addr();
        usort($res,'alistcmp');
        addr_display_result($res, true);
    }
    $oTemplate->display('footer.tpl');
    exit;
} elseif (!empty($addrquery)) {
    /* Do the search */
    if ($backend == -1) {
        $res = $abook->s_search($addrquery);
    } else {
        $res = $abook->s_search($addrquery, $backend);
    }

    if (!is_array($res)) {
        plain_error_message(_("Your search failed with the following error(s)") .':<br />'. nl2br(sm_encode_html_special_chars($abook->error)));
    } elseif (sizeof($res) == 0) {
        $oTemplate->assign('note', _("No persons matching your search were found"));
        $oTemplate->display('note.tpl');
    } else {
        addr_display_result($res);
    }
} else {
    // not first time display, not listall and search is empty
    // TODO: I think, this part of control structure is never reached.
    plain_error_message(_("Nothing to search"));
}

if ($addrquery == '' || sizeof($res) == 0) {
//FIXME don't echo HTML from core -- especially convoluted given that there is template code immediately above AND below this block
    echo '<div style="text-align: center;">'.
        addForm('compose.php','post','k', '', '', array(), TRUE);
    addr_insert_hidden();
    echo '<input type="submit" value="' . _("Return") . '" name="return" />' . "\n" .
         '</form></div></nobr>';
}

echo '<hr />';

$oTemplate->display('footer.tpl');
