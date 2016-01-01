<?php
/**
 * addrbook_search.php
 *
 * Handle addressbook searching in the popup window.
 *
 * NOTE: A lot of this code is similar to the code in
 *       addrbook_search_html.html -- If you change one,
 *       change the other one too!
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage addressbook
 */

/** This is the addrbook_search page */
define('PAGE_NAME', 'addrbook_search');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

include_once(SM_PATH . 'functions/forms.php');
include_once(SM_PATH . 'functions/addressbook.php');
include_once(SM_PATH . 'templates/util_addressbook.php');

/**
 * List search results
 * @param array $res Array of search results
 * @param bool $includesource [Default=true]
 * @return void
 */
function display_result($res, $includesource = true) {
    global $oTemplate, $oErrorHandler;

    if(sizeof($res) <= 0) return;

    $oTemplate->assign('compose_addr_pop', true);
    $oTemplate->assign('include_abook_name', $includesource);
    $oTemplate->assign('addresses', formatAddressList($res));
    
    $oTemplate->display('addrbook_search_list.tpl');    
}

/* ================= End of functions ================= */

/** lets get the global vars we may need */

if (! sqgetGlobalVar('show' , $show)) {
    $show = '';
}
if (! sqgetGlobalVar('query', $query, SQ_POST)) {
    $query = '';
}
if (! sqgetGlobalVar('listall', $listall, SQ_POST)) {
    unset($listall);
}
if (! sqgetGlobalVar('backend', $backend, SQ_POST)) {
    $backend = '';
}

displayHtmlHeader();
echo "<body>\n";

/** set correct value of $default_charset */
set_my_charset();

/* Empty search */
if (empty($query) && empty($show) && !isset($listall)) {
    $oTemplate->assign('note', sm_encode_html_special_chars(_("No persons matching your search were found")));
    $oTemplate->display('note.tpl');
#    exit;
}

/* Initialize addressbook, show init errors only in bottom frame */
$showerr=($show=='form' ? false : true);
$abook = addressbook_init($showerr);

/* Create search form (top frame) */
if ($show == 'form' && ! isset($listall)) {
    echo "<form name=\"sform\" target=\"abookres\" action=\"addrbook_search.php\" method=\"post\">\n";
    
    $oTemplate->assign('compose_addr_pop', true);
    $oTemplate->assign('backends', getBackends());
    $oTemplate->display('addressbook_search_form.tpl');
    
    echo "</form>\n";
} else {
    /**
     * List addresses (bottom frame)
     * If listall is set, list all entries in selected backend.
     * If $show is 'blank' (initial call of address book popup) - list
     * personal address book.
     */
    if ($show == 'blank' || isset($listall)) {

        if($backend != -1 || $show == 'blank') {
            if ($show == 'blank') {
                $backend = $abook->localbackend;
            }
            $res = $abook->list_addr($backend);

            if(is_array($res)) {
                usort($res,'alistcmp');
                display_result($res, false);
            } else {
                plain_error_message(sprintf(_("Unable to list addresses from %s"), $abook->backends[$backend]->sname));
            }
        } else {
            $res = $abook->list_addr();
            usort($res,'alistcmp');
            display_result($res, true);
        }

    } elseif (!empty($query)) {
        /* Do the search (listall is not set. query is set.)*/

        if($backend == -1) {
            $res = $abook->s_search($query);
        } else {
            $res = $abook->s_search($query, $backend);
        }

        if (!is_array($res)) {
            plain_error_message( _("Your search failed with the following error(s)") .':<br />'. nl2br(sm_encode_html_special_chars($abook->error)) );
        } elseif (sizeof($res) == 0) {
            $oTemplate->assign('note', _("No persons matching your search were found"));
            $oTemplate->display('note.tpl');
        } else {
            display_result($res);
        }
    } else {
        /**
         * listall is not set, query is not set or empty.
         * User hit search button without entering search expression.
         */
        plain_error_message(_("Nothing to search"));
    }
}

$oTemplate->display('footer.tpl');
