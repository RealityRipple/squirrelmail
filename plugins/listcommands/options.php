<?php

/**
 * SquirrelMail List Commands Plugin
 * options.php
 *
 * Shows options page for managing non-RFC-compliant list subscriptions.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage listcommands
 */


include_once('../../include/init.php');
include_once(SM_PATH . 'plugins/listcommands/functions.php');

global $listcommands_allow_non_rfc_list_management, $data_dir, $username;

// only allow management of non-RFC lists if admin deems necessary
//
@include_once(SM_PATH . 'plugins/listcommands/config.php');
if (!$listcommands_allow_non_rfc_list_management)
    return;


$lists = get_non_rfc_lists();



// remove list?
//
if (sqGetGlobalVar('deletelist', $deletelist, SQ_FORM) 
 && is_array($deletelist) && !empty($deletelist)) {

   // interface currently does not support multiple deletions at once
   // but we'll support it here anyway -- the index values of this
   // array are the only thing we care about and need to be the 
   // index number of the list to be deleted 
   //
   foreach (array_keys($deletelist) as $index)
      unset($lists[$index]);

    sort($lists);
    $temp_lists = array();
    foreach ($lists as $index => $list_addr)
        $temp_lists[] = $index . '_' . $list_addr;
    setPref($data_dir, $username, 'non_rfc_lists', implode(':', $temp_lists));

}



// add list?
//
if (sqGetGlobalVar('addlist', $ignore, SQ_FORM) 
 && sqGetGlobalVar('newlist', $newlist, SQ_FORM)) {

    $lists[] = $newlist;

    sort($lists);
    $temp_lists = array();
    foreach ($lists as $index => $list_addr)
        $temp_lists[] = $index . '_' . $list_addr;
    setPref($data_dir, $username, 'non_rfc_lists', implode(':', $temp_lists));

}



displayPageHeader($color);

$oTemplate->assign('lists', $lists);
$oTemplate->display('plugins/listcommands/non_rfc_lists.tpl');
$oTemplate->display('footer.tpl');


