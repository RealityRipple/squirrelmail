<?php

/**
 * attachment_common.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file provides the handling of often-used attachment types.
 *
 * $Id$
 */

/*****************************************************************/
/*** THIS FILE NEEDS TO HAVE ITS FORMATTING FIXED!!!           ***/
/*** PLEASE DO SO AND REMOVE THIS COMMENT SECTION.             ***/
/***    + Base level indent should begin at left margin, as    ***/
/***      the $attachment_common_show_images_list stuff below. ***/
/***    + All identation should consist of four space blocks   ***/
/***    + Tab characters are evil.                             ***/
/***    + all comments should use "slash-star ... star-slash"  ***/
/***      style -- no pound characters, no slash-slash style   ***/
/***    + FLOW CONTROL STATEMENTS (if, while, etc) SHOULD      ***/
/***      ALWAYS USE { AND } CHARACTERS!!!                     ***/
/***    + Please use ' instead of ", when possible. Note "     ***/
/***      should always be used in _( ) function calls.        ***/
/*** Thank you for your help making the SM code more readable. ***/
/*****************************************************************/

global $attachment_common_show_images_list;
$attachment_common_show_images_list = array();

  global $FileExtensionToMimeType, $attachment_common_types;
  $FileExtensionToMimeType = array('bmp'  => 'image/x-bitmap',
                                   'gif'  => 'image/gif',
                                   'htm'  => 'text/html',
                                   'html' => 'text/html',
                                   'jpg'  => 'image/jpeg',
                                   'jpeg' => 'image/jpeg',
                                   'php'  => 'text/plain',
                                   'png'  => 'image/png',
                                   'rtf'  => 'text/richtext',
                                   'txt'  => 'text/plain',
                                   'vcf'  => 'text/x-vcard');

  // Register browser-supported image types
  if (isset($attachment_common_types)) {
     // Don't run this before being logged in. That may happen
     // when plugins include mime.php
     foreach ($attachment_common_types as $val => $v) {
        if ($val == 'image/gif')
           register_attachment_common('image/gif',       'link_image');
        elseif (($val == 'image/jpeg' || $val == 'image/pjpeg') and
                (!isset($jpeg_done))) {
           $jpeg_done = 1;
           register_attachment_common('image/jpeg',      'link_image');
           register_attachment_common('image/pjpeg',     'link_image');
        }
        elseif ($val == 'image/png')
           register_attachment_common('image/png',       'link_image');
        elseif ($val == 'image/x-xbitmap')
           register_attachment_common('image/x-xbitmap', 'link_image');
     }
     unset($jpeg_done);
  }

  // Register text-type attachments
  register_attachment_common('message/rfc822', 'link_text');
  register_attachment_common('text/plain',     'link_text');
  register_attachment_common('text/richtext',  'link_text');

  // Register HTML
  register_attachment_common('text/html',      'link_html');

  // Register vcards
  register_attachment_common('text/x-vcard',   'link_vcard');

  // Register "unknown" attachments
  register_attachment_common('application/octet-stream', 'octet_stream');


/* Function which optimizes readability of the above code */

function register_attachment_common($type, $func) {
  global $squirrelmail_plugin_hooks;
  $squirrelmail_plugin_hooks['attachment ' . $type]['attachment_common'] =
                      'attachment_common_' . $func;
}


function attachment_common_link_text(&$Args)
{
  // If there is a text attachment, we would like to create a 'view' button
  // that links to the text attachment viewer.
  //
  // $Args[1] = the array of actions
  //
  // Use our plugin name for adding an action
  // $Args[1]['attachment_common'] = array for href and text
  //
  // $Args[1]['attachment_common']['text'] = What is displayed
  // $Args[1]['attachment_common']['href'] = Where it links to
  //
  // This sets the 'href' of this plugin for a new link.
  $Args[1]['attachment_common']['href'] = '../src/download.php?startMessage=' .
     $Args[2] . '&passed_id=' . $Args[3] . '&mailbox=' . $Args[4] .
     '&passed_ent_id=' . $Args[5] . '&override_type0=text&override_type1=plain';
  
  // If we got here from a search, we should preserve these variables
  if ($Args[8] && $Args[9])
     $Args[1]['attachment_common']['href'] .= '&where=' . 
     urlencode($Args[8]) . '&what=' . urlencode($Args[9]);

  // The link that we created needs a name.  "view" will be displayed for
  // all text attachments handled by this plugin.
  $Args[1]['attachment_common']['text'] = _("view");
  
  // Each attachment has a filename on the left, which is a link.
  // Where that link points to can be changed.  Just in case the link above
  // for viewing text attachments is not the same as the default link for
  // this file, we'll change it.
  //
  // This is a lot better in the image links, since the defaultLink will just
  // download the image, but the one that we set it to will format the page
  // to have an image tag in the center (looking a lot like this text viewer)
  $Args[6] = $Args[1]['attachment_common']['href'];
}


function attachment_common_link_html(&$Args)
{
  $Args[1]['attachment_common']['href'] = '../src/download.php?startMessage=' . 
     $Args[2] . '&passed_id=' . $Args[3] . '&mailbox=' . $Args[4] .
    '&passed_ent_id=' . $Args[5] . '&override_type0=text&override_type1=html';
  
  if ($Args[8] && $Args[9])
     $Args[1]['attachment_common']['href'] .= '&where=' . 
     urlencode($Args[8]) . '&what=' . urlencode($Args[9]);

  $Args[1]['attachment_common']['text'] = _("view");
  
  $Args[6] = $Args[1]['attachment_common']['href'];
}


function attachment_common_link_image(&$Args)
{
  global $attachment_common_show_images, $attachment_common_show_images_list;
  
  $info['passed_id'] = $Args[3];
  $info['mailbox'] = $Args[4];
  $info['ent_id'] = $Args[5];
  
  $attachment_common_show_images_list[] = $info;
  
  $Args[1]['attachment_common']['href'] = '../src/image.php?startMessage=' .
     $Args[2] . '&passed_id=' . $Args[3] . '&mailbox=' . $Args[4] .
     '&passed_ent_id=' . $Args[5];
  
  if ($Args[8] && $Args[9])
     $Args[1]['attachment_common']['href'] .= '&where=' . 
     urlencode($Args[8]) . '&what=' . urlencode($Args[9]);

  $Args[1]['attachment_common']['text'] = _("view");
  
  $Args[6] = $Args[1]['attachment_common']['href'];
}


function attachment_common_link_vcard(&$Args)
{
  $Args[1]['attachment_common']['href'] = '../src/vcard.php?startMessage=' .
     $Args[2] . '&passed_id=' . $Args[3] . '&mailbox=' . $Args[4] .
     '&passed_ent_id=' . $Args[5];

  if (isset($where) && isset($what))
     $Args[1]['attachment_common']['href'] .= '&where=' . 
     urlencode($Args[8]) . '&what=' . urlencode($Args[9]);

  $Args[1]['attachment_common']['text'] = _("Business Card");

  $Args[6] = $Args[1]['attachment_common']['href'];
}


function attachment_common_octet_stream(&$Args)
{
   global $FileExtensionToMimeType;
   
   do_hook('attachment_common-load_mime_types');
   
   ereg('\\.([^\\.]+)$', $Args[7], $Regs);
  
   $Ext = strtolower($Regs[1]);
   
   if ($Ext == '' || ! isset($FileExtensionToMimeType[$Ext]))
       return;       
   
   $Ret = do_hook('attachment ' . $FileExtensionToMimeType[$Ext], 
       $Args[1], $Args[2], $Args[3], $Args[4], $Args[5], $Args[6], 
       $Args[7], $Args[8], $Args[9]);
       
   foreach ($Ret as $a => $b) {
       $Args[$a] = $b;
   }
}

?>
