<?php

   /**
    **  mail_fetch/functions.php
    **
    **  Copyright (c) 1999-2002 The SquirrelMail Project Team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Functions for the mailfetch plugin.
    **
    **  Original code from LexZEUS <lexzeus@mifinca.com>
    **  and josh@superfork.com (extracted from php manual)
    **  Adapted for MailFetch by Philippe Mingo <mingo@rotedic.com>
    **
    **  $Id$
    **/

    function hex2bin( $data ) {

        /* Original code by josh@superfork.com */

        $len = strlen($data);
        $newdata = '';
        for( $i=0; $i < $len; $i += 2 ) {
            $newdata .= pack( "C", hexdec( substr( $data, $i, 2) ) );
        }
        return $newdata;
    }

    function mf_keyED( $txt ) {

        global $MF_TIT;

        if( !isset( $MF_TIT ) ) {
            $MF_TIT = "MailFetch Secure for SquirrelMail 1.x";
        }

        $encrypt_key = md5( $MF_TIT );
        $ctr = 0;
        $tmp = "";
        for( $i = 0; $i < strlen( $txt ); $i++ ) {
            if( $ctr == strlen( $encrypt_key ) ) $ctr=0;
            $tmp.= substr( $txt, $i, 1 ) ^ substr( $encrypt_key, $ctr, 1 );
            $ctr++;
        }
        return $tmp;
    }

    function encrypt( $txt ) {

        srand( (double) microtime() * 1000000 );
        $encrypt_key = md5( rand( 0, 32000 ) );
        $ctr = 0;
        $tmp = "";
        for( $i = 0; $i < strlen( $txt ); $i++ ) {
        if ($ctr==strlen($encrypt_key)) $ctr=0;
            $tmp.= substr($encrypt_key,$ctr,1) .
                (substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1));
        $ctr++;
        }
        return bin2hex( mf_keyED( $tmp ) );

    }

    function decrypt( $txt ) {

        $txt = mf_keyED( hex2bin( $txt ) );
        $tmp = '';
        for ( $i=0; $i < strlen( $txt ); $i++ ) {
            $md5 = substr( $txt, $i, 1 );
            $i++;
            $tmp.= ( substr( $txt, $i, 1 ) ^ $md5 );
        }
        return $tmp;
    }

?>
