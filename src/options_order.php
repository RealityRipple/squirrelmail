<?php

/**
 * options_order.php
 *
 * Displays messagelist column order options
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage prefs
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
include_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'functions/global.php');
include_once(SM_PATH . 'functions/display_messages.php');
include_once(SM_PATH . 'functions/imap.php');
include_once(SM_PATH . 'functions/plugin.php');
include_once(SM_PATH . 'functions/html.php');
include_once(SM_PATH . 'functions/forms.php');
include_once(SM_PATH . 'functions/arrays.php');
//require_once(SM_PATH . 'functions/options.php');

/* get globals */
if (sqgetGlobalVar('num',       $num,       SQ_GET)) {
   $num = (int) $num;
} else {
   $num = false;
}
if (!sqgetGlobalVar('method', $method)) {
    $method = '';
} else {
    $method = htmlspecialchars($method);
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
   $aMailboxPrefs = unserialize(getPref($data_dir, $username, "pref_".$iAccount.'_'.urldecode($mailbox)));
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

displayPageHeader($color, 'None', (isset($optpage_data['xtra']) ? $optpage_data['xtra'] : ''));


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



viewOrderForm($available, $index_order,$opts,urldecode($mailbox));


// FOOD for html designers
function viewOrderForm($aColumns, $aOrder, $aOpts, $mailbox) {
   global $color;
?>

  <table align="center" width="95%" border="0" cellpadding="1" cellspacing="0">
    <tr>
      <td align="center" bgcolor="<?php echo $color[0];?>">
        <b> <?php echo _("Options");?> - <?php echo _("Index Order");?> </b>
        <table width="100%" border="0" cellpadding="8" cellspacing="0">
          <tr>
            <td align="center" bgcolor="<?php echo $color[4];?>">
              <table width="65%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td>
                    <?php echo _("The index order is the order that the columns are arranged in the message index. You can add, remove, and move columns around to customize them to fit your needs.");?>
                  </td>
                </tr>
              </table>
              <br>

<?php if (count($aOrder)) { ?>
              <table cellspacing="0" cellpadding="0" border="0">
<?php     foreach($aOrder as $i => $iCol) {
             $sQuery = "&amp;num=$iCol";
             if (isset($mailbox) && $mailbox) {
                 $sQuery .= '&amp;mailbox='.urlencode($mailbox);
             }

?>
                <tr>
<?php         if ($i) { ?>
                  <td><small><a href="options_order.php?method=move&amp;positions=-1&amp;num=<?php echo $sQuery; ?>"> <?php echo _("up");?> </a></small></td>
<?php         } else { ?>
                  <td>&nbsp;</td>
<?php         } // else ?>
                  <td><small>&nbsp;|&nbsp;</small></td>
<?php         if ($i < count($aOrder) -1) { ?>
                  <td><small><a href="options_order.php?method=move&amp;positions=1&amp;num=<?php echo $sQuery; ?>"> <?php echo _("down");?> </a></small></td>
<?php         } else { ?>
                  <td>&nbsp;</td>
<?php         } // else ?>
                  <td><small>&nbsp;|&nbsp;</small></td>
<?php
              /* Always show the subject */
              if ($iCol !== SQM_COL_SUBJ && $iCol !== SQM_COL_FLAGS) {
?>
                  <td><small><a href="options_order.php?method=remove&amp;num=<?php echo $sQuery; ?>"> <?php echo _("remove");?> </a></small></td>
<?php         } else { ?>
                  <td>&nbsp;</td>
<?php         } // else ?>
                  <td><small>&nbsp;|&nbsp;</small></td>
                  <td><?php echo $aColumns[$iCol]; ?></td>
                </tr>
<?php
          } // foreach
      } // if
?>
              </table>

<?php
    if (count($aOpts)) {
        echo addForm('options_order.php', 'get', 'f');
        echo addSelect('num', $aOpts, '', TRUE);
        echo addHidden('method', 'add');
        if (isset($mailbox) && $mailbox) {
            echo addHidden('mailbox', urlencode($mailbox));
        }
        echo addSubmit(_("Add"), 'submit');
        echo '</form>';
    }
?>
          <p><a href="../src/options.php"><?php echo _("Return to options page");?></a></p><br>
        </td></tr>
      </table>
    </td></tr>
  </table>

<?php
}
$oTemplate->display('footer.tpl');
?>