<?php
/**
 * options_order.php
 *
 * Displays messagelist column order options
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage prefs
 */

/** This is the options_order page */
define('PAGE_NAME', 'options_order');

/**
 * Include the SquirrelMail initialization file.
 */
require('../include/init.php');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/forms.php');

/* get globals */
if (sqgetGlobalVar('num',       $num,       SQ_GET)) {
   $num = (int) $num;
} else {
   $num = false;
}
if (!sqgetGlobalVar('method', $method)) {
    $method = '';
} else {
    $method = sm_encode_html_special_chars($method);
}
if (!sqgetGlobalVar('positions', $pos, SQ_GET)) {
    $pos = 0;
} else {
    $pos = (int) $pos;
}

if (!sqgetGlobalVar('account', $account, SQ_GET)) {
    $iAccount = 0;
} else {
    $iAccount = (int) $account;
}

if (sqgetGlobalVar('mailbox', $mailbox, SQ_GET)) {
   $aMailboxPrefs = unserialize(getPref($data_dir, $username, "pref_".$iAccount.'_'.$mailbox));
   if (isset($aMailboxPrefs[MBX_PREF_COLUMNS])) {
       $index_order = $aMailboxPrefs[MBX_PREF_COLUMNS];
   }
} else {
    $index_order_ser = getPref($data_dir, $username, 'index_order');
    if ($index_order_ser) {
        $index_order=unserialize($index_order_ser);
    }
}
if (!isset($index_order)) {
    if (isset($internal_date_sort) && $internal_date_sort == false) {
        $index_order = array(SQM_COL_CHECK,SQM_COL_FROM,SQM_COL_DATE,SQM_COL_FLAGS,SQM_COL_ATTACHMENT,SQM_COL_PRIO,SQM_COL_SUBJ);
    } else {
        $index_order = array(SQM_COL_CHECK,SQM_COL_FROM,SQM_COL_INT_DATE,SQM_COL_FLAGS,SQM_COL_ATTACHMENT,SQM_COL_PRIO,SQM_COL_SUBJ);
    }
}

if (!sqgetGlobalVar('account', $account,  SQ_GET)) {
   $account = 0; // future work, multiple imap accounts
} else {
   $account = (int) $account;
}

/* end of get globals */

/***************************************************************/
/* Finally, display whatever page we are supposed to show now. */
/***************************************************************/

displayPageHeader($color, null, (isset($optpage_data['xtra']) ? $optpage_data['xtra'] : ''));


/**
 * Change the column order of a mailbox
 *
 * @param array  $index_order (reference) contains an ordered list with columns
 * @param string $method action to take, move, add and remove are supported
 * @param int    $num target column
 * @param int    $pos positions to move a column in the index_order array
 * @return bool  $r A change in the ordered list took place.
 */
function change_columns_list(&$index_order,$method,$num,$pos=0) {
    $r = false;
    switch ($method) {
      case 'move': $r = sqm_array_move_value($index_order,$num,$pos); break;
      case 'add':
          $index_order[] = (int) $num;
          $r = true;
          /**
           * flush the cache in order to retrieve the new columns
           */
          sqsession_unregister('mailbox_cache');
          break;
      case 'remove':
        if(in_array($num, $index_order)) {
            unset($index_order[array_search($num, $index_order)]);
            $index_order = array_values($index_order);
            $r = true;
        }
        break;
      default: break;
    }
    return $r;
}

/**
 * Column to string translation array
 */
$available[SQM_COL_CHECK]      = _("Checkbox");
$available[SQM_COL_FROM]       = _("From");
$available[SQM_COL_DATE]       = _("Date");
$available[SQM_COL_SUBJ]       = _("Subject");
$available[SQM_COL_FLAGS]      = _("Flags");
$available[SQM_COL_SIZE]       = _("Size");
$available[SQM_COL_PRIO]       = _("Priority");
$available[SQM_COL_ATTACHMENT] = _("Attachments");
$available[SQM_COL_INT_DATE]   = _("Received");
$available[SQM_COL_TO]         = _("To");
$available[SQM_COL_CC]         = _("Cc");
$available[SQM_COL_BCC]        = _("Bcc");

if (change_columns_list($index_order,$method,$num,$pos)) {
    if ($method) {
        // TODO, bound index_order to mailbox and make a difference between the global index_order and mailbox bounded index_order
        setPref($data_dir, $username, 'index_order', serialize($index_order));
    }
}


$opts = array();
if (count($index_order) != count($available)) {
    for ($i=0; $i < count($available); $i++) {
        if (!in_array($i,$index_order)) {
             $opts[$i] = $available[$i];
         }
    }
}

// FIXME: why are we using this?  $PHP_SELF is already a global var processed (and therefore trustworthy) by init.php
sqgetGlobalVar('PHP_SELF', $PHP_SELF, SQ_SERVER);
$x = isset($mailbox) && $mailbox ? '&amp;mailbox='.urlencode($mailbox) : '';

$oTemplate->assign('fields', $available);
$oTemplate->assign('current_order', $index_order);
$oTemplate->assign('not_used', $opts);
$oTemplate->assign('always_show', array(SQM_COL_SUBJ, SQM_COL_FLAGS));

// FIXME: (related to the above) $PHP_SELF might already have a query string... don't assume otherwise here by adding the ? sign!!
$oTemplate->assign('move_up', $PHP_SELF .'?method=move&amp;positions=-1'. $x .'&amp;num=');
$oTemplate->assign('move_down', $PHP_SELF .'?method=move&amp;positions=1'. $x .'&amp;num=');
$oTemplate->assign('remove', $PHP_SELF .'?method=remove'. $x .'&amp;num=');
$oTemplate->assign('add', $PHP_SELF.'?method=add'.$x.'&amp;num=');
$oTemplate->assign('addField_action', $PHP_SELF);

$oTemplate->display('options_order.tpl');

$oTemplate->display('footer.tpl');
