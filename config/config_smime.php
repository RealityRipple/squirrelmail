<?php

/**
  * SquirrelMail S/MIME Verification Plugin
  *
  * Copyright (c) 2015 Walter Hoehlhubmer <walter.h@mathemainzel.info>
  * Copyright (c) 2005-2012 Paul Lesniewski <paul@squirrelmail.org>
  * Copyright (c) 2005 Khedron Wilk <khedron@wilk.se>
  * Copyright (c) 2004 Scott Heavner
  * Copyright (c) 2003 Antonio Vasconcelos <vasco@threatconn.com>
  * Copyright (c) 2001-2003 Wouter Teepe <wouter@teepe.com>
  *
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * @package plugins
  * @subpackage smime
  *
  */

global $data_dir, $color,
       $cert_in_dir, $row_highlite_color;

global $openssl_cmds, $tmp_dir;

   // This is the color used in the background of the signature
   // verification information presented to the user.  $color[9]
   // may be subdued in some display themes, $color[16] will usually
   // stand out rather strongly.  You may add any color you would
   // like here, including static ones.  This information may or may
   // not be used under SquirrelMail 1.5.2+.
   //
   // $row_highlite_color = $color[9];
   // $row_highlite_color = $color[16];
   // $row_highlite_color = '#ff9933';
   //
   $row_highlite_color = $color[16];


   // This is the directory where signer ceritificates are stored
   // for analysis.  It must be readable and writeable by the user
   // your web server runs as.  This setting's default value usually
   // does not need to be changed.
   //
   $cert_in_dir = $GLOBALS['siteRoot'].'/rrs/.maildata/data/certs-in/';



   // This is the full path to the OpenSSL cmds shell script.
   //
   $openssl_cmds = SM_PATH . 'plugins/smime/openssl-cmds.sh';


   // This is the directory where temporary files are stored.
   // It must be readable and writeable by the user your web server runs as.
   // This setting's default value usually does not need to be changed.
   //
   $tmp_dir = $GLOBALS['siteRoot'].'/rrs/.maildata/data/tmp/';
