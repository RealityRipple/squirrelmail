<?php

/**
 * tree.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This code provides various string manipulation functions that are
 * used by the rest of the Squirrelmail code.
 *
 * $Id$
 */

require_once(SM_PATH . 'functions/imap.php');

/**
 * findParentForChild
 *
 * Recursive function to find the correct parent for a new node
 */

function findParentForChild($value, $treeIndexToStart, $tree) {
    // is $value in $tree[$treeIndexToStart]['value']
    if ((isset($tree[$treeIndexToStart])) && (strstr($value, $tree[$treeIndexToStart]['value']))) {
        // do I have children, if not then must be a childnode of the current node
        if ($tree[$treeIndexToStart]['doIHaveChildren']) {
            // loop through each subNode checking to see if we are a subNode of one of them
            for ($i=0;$i< count($tree[$treeIndexToStart]['subNodes']);$i++) {
            $result = findParentForChild($value, $tree[$treeIndexToStart]['subNodes'][$i], $tree);
            if ($result > -1)
                return $result;
            }
            // if we aren't a child of one of the subNodes, must be a child of current node
            return $treeIndexToStart;
        } else
            return $treeIndexToStart;
    } else {
        // we aren't a child of this node at all
        return -1;
    }
}

function addChildNodeToTree($comparisonValue, $value, &$tree) {
    $parentNode = findParentForChild($comparisonValue, 0, $tree);

    // create a new subNode
    $newNodeIndex = count($tree);
    $tree[$newNodeIndex]['value'] = $value;
    $tree[$newNodeIndex]['doIHaveChildren'] = false;

    if ($tree[$parentNode]['doIHaveChildren'] == false) {
        // make sure the parent knows it has children
        $tree[$parentNode]['subNodes'][0] = $newNodeIndex;
        $tree[$parentNode]['doIHaveChildren'] = true;
    } else {
        $nextSubNode = count($tree[$parentNode]['subNodes']);
        // make sure the parent knows it has children
        $tree[$parentNode]['subNodes'][$nextSubNode] = $newNodeIndex;
    }
}

function walkTreeInPreOrderEmptyTrash($index, $imap_stream, $tree) {
    global $trash_folder;
    if ($tree[$index]['doIHaveChildren']) {
        for ($j = 0; $j < count($tree[$index]['subNodes']); $j++) {
            walkTreeInPreOrderEmptyTrash($tree[$index]['subNodes'][$j], $imap_stream, $tree);
        }
        if ($tree[$index]['value'] != $trash_folder) {
            sqimap_mailbox_delete($imap_stream, $tree[$index]['value']);
        } else {
            $mbx_response = sqimap_mailbox_select($imap_stream, $trash_folder);
	    if ($mbx_response['EXISTS'] > 0) {
               sqimap_messages_flag ($imap_stream, 1, '*', 'Deleted', true);
               sqimap_mailbox_expunge($imap_stream, $trash_folder, true);
            }
        }
    } else {
        if ($tree[$index]['value'] != $trash_folder) {
            sqimap_mailbox_delete($imap_stream, $tree[$index]['value']);
        } else {
	    $mbx_response = sqimap_mailbox_select($imap_stream, $trash_folder);	    
	    if ($mbx_response['EXISTS'] > 0) {
        	sqimap_messages_flag ($imap_stream, 1, '*', 'Deleted', true);
        	sqimap_mailbox_expunge($imap_stream, $trash_folder, true);
            }
        }
    }
}


function walkTreeInPreOrderDeleteFolders($index, $imap_stream, $tree) {
    if ($tree[$index]['doIHaveChildren']) {
        for ($j = 0; $j < count($tree[$index]['subNodes']); $j++) {
            walkTreeInPreOrderDeleteFolders($tree[$index]['subNodes'][$j], $imap_stream, $tree);
        }
        sqimap_mailbox_delete($imap_stream, $tree[$index]['value']);
    } else {
        sqimap_mailbox_delete($imap_stream, $tree[$index]['value']);
    }
}

function walkTreeInPostOrderCreatingFoldersUnderTrash($index, $imap_stream, $tree, $topFolderName) {
    global $trash_folder, $delimiter;

    $position = strrpos($topFolderName, $delimiter) + 1;
    $subFolderName = substr($tree[$index]['value'], $position);

    if ($tree[$index]['doIHaveChildren']) {
        sqimap_mailbox_create($imap_stream, $trash_folder . $delimiter . $subFolderName, "");
        $mbx_response = sqimap_mailbox_select($imap_stream, $tree[$index]['value']);	    
	$messageCount = $mbx_response['EXISTS'];
        if ($messageCount > 0)
            sqimap_messages_copy($imap_stream, 1, '*', $trash_folder . $delimiter . $subFolderName);
        for ($j = 0;$j < count($tree[$index]['subNodes']); $j++)
            walkTreeInPostOrderCreatingFoldersUnderTrash($tree[$index]['subNodes'][$j], $imap_stream, $tree, $topFolderName);
    } else {
        sqimap_mailbox_create($imap_stream, $trash_folder . $delimiter . $subFolderName, '');
        $mbx_response = sqimap_mailbox_select($imap_stream, $tree[$index]['value']);
        $messageCount = $mbx_response['EXISTS'];
        if ($messageCount > 0)
            sqimap_messages_copy($imap_stream, 1, '*', $trash_folder . $delimiter . $subFolderName);
    }
}

function simpleWalkTreePre($index, $tree) {
    if ($tree[$index]['doIHaveChildren']) {
        for ($j = 0; $j < count($tree[$index]['subNodes']); $j++) {
            simpleWalkTreePre($tree[$index]['subNodes'][$j], $tree);
        }
        echo $tree[$index]['value'] . '<br>';
    } else {
        echo $tree[$index]['value'] . '<br>';
    }
}
?>
