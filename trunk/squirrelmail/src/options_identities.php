<?php

/**
 * options_identities.php
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Display Identities Options
 *
 * $Id$
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/html.php');

/* POST data var names are dynamic because 
   of the possible multiple idents so lets get
   them all
*/
if (!empty($_POST)) {
    extract($_POST);
}
/* got 'em all */

    if (isset($return)) {
       SaveUpdateFunction();
       header('Location: options_personal.php');
       exit();
    }
    
    displayPageHeader($color, 'None');
 
    $Info = do_hook('options_identities_process', 0);
    if ($Info[1]) {
        SaveUpdateFunction();
    }
    
    if (CheckAndDoDefault() || CheckAndDoPromote()) {
       SaveUpdateFunction();
    }
    if (isset($update) || CheckForDelete()) {
        SaveUpdateFunction();
    }
 
   do_hook('options_identities_top');
   LoadInfo($full_name, $email_address, $reply_to, $signature, '');
   $td_str = '';
   $td_str .= '<form name="f" action="options_identities.php" method="post"><br>';
   $td_str .= ShowTableInfo($full_name, $email_address, $reply_to, $signature, '');
  
   $num = 1;
   while (LoadInfo($full_name, $email_address, $reply_to, $signature, $num)) {
       $td_str .= html_tag( 'tr',
                          html_tag( 'th', sprintf (_("Alternate Identity %d"), $num), 'center', '', 'colspan="2"' ) ,
                      '', $color[9]);
       $td_str .= ShowTableInfo($full_name, $email_address, $reply_to, $signature, $num);
       $num ++;
       }

   echo '<br>' . 
   html_tag( 'table', "\n" .
       html_tag( 'tr', "\n" .
           html_tag( 'td', "\n" .
               '<b>'. _("Options") . ' - ' . _("Advanced Identities") .'</b><br>' .
               html_tag( 'table', "\n" .
                   html_tag( 'tr', "\n" .
                       html_tag( 'td', "\n" .
                           html_tag( 'table', "\n" .
                               html_tag( 'tr', "\n" .
                                   html_tag( 'th', _("Default Identity"), 'center', '', 'colspan="2"' ) ,
                                   '', $color[9]) . "\n" .
                                   $td_str . "\n" .
                               html_tag( 'tr',
                                   html_tag( 'th', _("Add a New Identity") . ShowTableInfo('', '', '', '', $num), 'center', '', 'colspan="2"' ) ,
                               '', $color[9]) ,
                            '', '', 'width="80%" cellpadding="2" cellspacing="0" border="0"' ) ,
                       'center', $color[4] )
                   ) ,
               '', '', 'width="100%" border="0" cellpadding="1" cellspacing="1"' ) ,
           'center', $color[0] )
       ) ,
   'center', '', 'width="95%" border="0" cellpadding="2" cellspacing="0"' ) .

   '</body></html>';

    function SaveUpdateFunction() {
        global $username, $data_dir, $full_name, $email_address, $reply_to, $signature;

        $i = 1;
        $fakeI = 1;
        $name = 'form_for_' . $i;
        global $$name;
        while (isset($$name))
        {
            $name = 'delete_' . $i;
            global $$name;
            if (isset($$name)) {
                $fakeI --;
            } else {
                do_hook('options_identities_renumber', $i, $fakeI);
                $filled = 0;

                $name = 'full_name' . $i;
                global $$name;
            if ($$name != '')
                $filled ++;
                setPref($data_dir, $username, 'full_name' . $fakeI, $$name);

                $name = 'email_address' . $i;
                global $$name;
            if ($$name != '')
                $filled ++;
                setPref($data_dir, $username, 'email_address' . $fakeI, $$name);

                $name = 'reply_to' . $i;
                global $$name;
            if ($$name != '')
                $filled ++;
                setPref($data_dir, $username, 'reply_to' . $fakeI, $$name);

                $name = 'signature' . $i;
                global $$name;
            if ($$name != '')
                $filled ++;
                setSig($data_dir, $username, $fakeI, $$name);

            if ($filled == 0)
                $fakeI --;
            }

            $fakeI ++;
            $i ++;
            $name = 'form_for_' . $i;
            global $$name;
        }

        setPref($data_dir, $username, 'identities', $fakeI);

        while ($fakeI != $i)
        {
            removePref($data_dir, $username, 'full_name' . $fakeI);
            removePref($data_dir, $username, 'email_address' . $fakeI);
            removePref($data_dir, $username, 'reply_to' . $fakeI);
            setSig($data_dir, $username, $fakeI, "");
            $fakeI ++;
        }

        setPref($data_dir, $username, 'full_name', $full_name);
        setPref($data_dir, $username, 'email_address', $email_address);
        setPref($data_dir, $username, 'reply_to', $reply_to);
        setSig($data_dir, $username, "g", $signature);
        
    }

    function CheckAndDoDefault() {
        global $username, $data_dir, $full_name, $email_address, $reply_to, $signature;

        $i = 1;
        $name = 'form_for_' . $i;
        global $$name;
        while (isset($$name))
        {
            $name = 'make_default_' . $i;
            global $$name;
            if (isset($$name)) {
                do_hook('options_identities_renumber', $i, 'default');
                global $full_name, $email_address, $reply_to, $signature;

                $name = 'full_name' . $i;
                global $$name;
                $temp = $full_name;
                $full_name = $$name;
                $$name = $temp;

                $name = 'email_address' . $i;
                global $$name;
                $temp = $email_address;
                $email_address = $$name;
                $$name = $temp;

                $name = 'reply_to' . $i;
                global $$name;
                $temp = $reply_to;
                $reply_to = $$name;
                $$name = $temp;

                $name = 'signature' . $i;
                global $$name;
                $temp = $signature;
                $signature = $$name;
                $$name = $temp;


                return true;
            }

            $i ++;
            $name = 'form_for_' . $i;
            global $$name;
        }
        return FALSE;
    }

    function CheckForDelete() {
        global $username, $data_dir, $full_name, $email_address, $reply_to, $signature;

        $i = 1;
        $name = 'form_for_' . $i;
        global $$name;
        while (isset($$name))
        {
            $name = 'delete_' . $i;
            global $$name;
            if (isset($$name)) {
                return true;
            }

            $i ++;
            $name = 'form_for_' . $i;
            global $$name;
        }
        return false;
    }

    function CheckAndDoPromote() {
        global $username, $data_dir, $full_name, $email_address, $reply_to;

        $i = 1;
        $name = 'form_for_' . $i;
        global $$name;
        while (isset($$name)) {
            $name = 'promote_' . $i;
            global $$name;
            if (isset($$name) && $i > 1) {
                do_hook('options_identities_renumber', $i, $i - 1);

                $nameA = 'full_name' . $i;
                $nameB = 'full_name' . ($i - 1);
                global $$nameA, $$nameB;
                $temp = $$nameA;
                $$nameA = $$nameB;
                $$nameB = $temp;
    
                $nameA = 'email_address' . $i;
                $nameB = 'email_address' . ($i - 1);
                global $$nameA, $$nameB;
                $temp = $$nameA;
                $$nameA = $$nameB;
                $$nameB = $temp;
    
                $nameA = 'reply_to' . $i;
                $nameB = 'reply_to' . ($i - 1);
                global $$nameA, $$nameB;
                $temp = $$nameA;
                $$nameA = $$nameB;
                $$nameB = $temp;

            $nameA = 'signature' . $i;
            $nameB = 'signature' . ($i - 1);
            global $$nameA, $$nameB;
            $temp = $$nameA;
            $$nameA = $$nameB;
            $$nameB = $temp;

                return true;
            }

            $i ++;
            $name = 'form_for_' . $i;
            global $$name;
        }
        return false;
    }

    function LoadInfo(&$n, &$e, &$r, &$s, $post) {
        global $username, $data_dir;

        $n = getPref($data_dir, $username, 'full_name' . $post);
        $e = getPref($data_dir, $username, 'email_address' . $post);
        $r = getPref($data_dir, $username, 'reply_to' . $post);
        if ($post == '')
           $post = 'g';
        $s = getSig($data_dir,$username,$post);

        if ($n != '' || $e != '' || $r != '' || $s != '')
            return true;
    }

function sti_input( $title, $hd, $data, $post, $bg ) {
    $return_val = html_tag( 'tr',
                           html_tag( 'td', $title . ':', 'right', '', 'nowrap' ) .
                           html_tag( 'td', '<input size="50" type="text" value="' . htmlspecialchars($data) . '" name="' . $hd . $post . '">' , 'left' ) ,
                       '', $bg );
     return ($return_val);
}

function sti_textarea( $title, $hd, $data, $post, $bg ) {
    $return_val = html_tag( 'tr',
                           html_tag( 'td', $title . ':', 'right', '', 'nowrap' ) .
                           html_tag( 'td', '<textarea cols="50" rows="5" name="' . $hd . $post . '">' . htmlspecialchars($data) . '</textarea>' , 'left' ) ,
                       '', $bg );
     return ($return_val);
}

function ShowTableInfo($full_name, $email_address, $reply_to, $signature, $post) {
    global $color;

    $OtherBG = $color[0];
    if ($full_name == '' && $email_address == '' && $reply_to == '' && $signature == '')
        $OtherBG = '';

    if ($full_name == '' && $email_address == '' && $reply_to == '' && $signature == '')
        $isEmptySection = true;
    else
        $isEmptySection = false;

    $return_val = '';
    $return_val .= sti_input( _("Full Name"), 'full_name', $full_name, $post, $OtherBG );
    $return_val .= sti_input( _("E-Mail Address"), 'email_address', $email_address, $post, $OtherBG );
    $return_val .= sti_input( _("Reply To"), 'reply_to', $reply_to, $post, $OtherBG );
    $return_val .= sti_textarea( _("Signature"), 'signature', $signature, $post, $OtherBG );

    do_hook('options_identities_table', $OtherBG, $isEmptySection, $post);
    $return_val .= html_tag( 'tr', '', '', $OtherBG);
    $return_val .= html_tag( 'td', '&nbsp;', 'left' );
    $return_val .= html_tag( 'td', '', 'left' );
    $return_val .= '<input type=hidden name="form_for_'. $post .'" value="1">';
    $return_val .= '<input type="submit" name="update" value="' . _("Save / Update") . '">';


    if (! $isEmptySection && $post != '') {
        $return_val .= '<input type="submit" name="make_default_' . $post . '" value="'.
             _("Make Default") . '">'.
             '<input type=submit name="delete_' . $post . '" value="'.
             _("Delete") . '">';
    }
    if (! $isEmptySection && $post != '' && $post > 1) {
        $return_val .= '<input type=submit name="promote_' . $post . '" value="'.
             _("Move Up") . '">';
    }
    do_hook('options_identities_buttons', $isEmptySection, $post);
    $return_val .=  '</td></tr>'.
         html_tag( 'tr', html_tag( 'td', '&nbsp;', 'left', '', 'colspan="2"' ));

    return ($return_val);
}
?>
