<?php

/**
 * auth.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Contains functions used to do authentication.
 *
 * $Id$
 */

/* Put in a safety net here, in case a naughty admin didn't run conf.pl when they upgraded */

if (! isset($smtp_auth_mech)) {
  $smtp_auth_mech = 'none';
}

if (! isset($imap_auth_mech)) {
  $imap_auth_mech = 'plain';
}

if (! isset($use_imap_tls)) {
  $use_imap_tls = false;
}

if (! isset($use_smtp_tls)) {
  $use_smtp_tls = false;
}

function is_logged_in() {

    if ( sqsession_is_registered('user_is_logged_in') ) {
        return;
    } else {
        global $PHP_SELF, $session_expired_post, 
	       $session_expired_location;

        /*  First we store some information in the new session to prevent
         *  information-loss.
         */
	 
	$session_expired_post = $_POST;
        $session_expired_location = $PHP_SELF;
        if (!sqsession_is_registered('session_expired_post')) {    
           sqsession_register($session_expired_post,'session_expired_post');
        }
        if (!sqsession_is_registered('session_expired_location')) {
           sqsession_register($session_expired_location,'session_expired_location');
        }
        include_once( '../functions/display_messages.php' );
        logout_error( _("You must be logged in to access this page.") );
        exit;
    }
}

function cram_md5_response ($username,$password,$challenge) {

/* Given the challenge from the server, supply the response using
   cram-md5 (See RFC 2195 for details)
*/
$challenge=base64_decode($challenge);
$hash=bin2hex(hmac_md5($challenge,$password));
$response=base64_encode($username . " " . $hash) . "\r\n";
return $response;
}

function digest_md5_response ($username,$password,$challenge,$service,$host) {
/* Given the challenge from the server, calculate and return the response-string
   for digest-md5 authentication.  (See RFC 2831 for more details) */
  $result=digest_md5_parse_challenge($challenge);
  
// verify server supports qop=auth
  // $qop = explode(",",$result['qop']);
  //if (!in_array("auth",$qop)) {
    // rfc2831: client MUST fail if no qop methods supported
   // return false;
  //}
  $cnonce = base64_encode(bin2hex(hmac_md5(microtime())));
  $ncount = "00000001";

  /* This can be auth (authentication only), auth-int (integrity protection), or
     auth-conf (confidentiality protection).  Right now only auth is supported.
	 DO NOT CHANGE THIS VALUE */
  $qop_value = "auth";

  $digest_uri_value = $service . '/' . $host;

  // build the $response_value
  //FIXME This will probably break badly if a server sends more than one realm
  $string_a1 = utf8_encode($username).":";
  $string_a1 .= utf8_encode($result['realm']).":";
  $string_a1 .= utf8_encode($password);
  $string_a1 = hmac_md5($string_a1);
  $A1 = $string_a1 . ":" . $result['nonce'] . ":" . $cnonce;
  $A1 = bin2hex(hmac_md5($A1));
  $A2 = "AUTHENTICATE:$digest_uri_value";
  // If qop is auth-int or auth-conf, A2 gets a little extra
  if ($qop_value != 'auth') {
    $A2 .= ':00000000000000000000000000000000';
  }
  $A2 = bin2hex(hmac_md5($A2));

  $string_response = $result['nonce'] . ':' . $ncount . ':' . $cnonce . ':' . $qop_value;
  $response_value = bin2hex(hmac_md5($A1.":".$string_response.":".$A2));

  $reply = 'charset=utf-8,username="' . $username . '",realm="' . $result["realm"] . '",';
  $reply .= 'nonce="' . $result['nonce'] . '",nc=' . $ncount . ',cnonce="' . $cnonce . '",';
  $reply .= "digest-uri=\"$digest_uri_value\",response=$response_value";
  $reply .= ',qop=' . $qop_value;
  $reply = base64_encode($reply);
  return $reply . "\r\n";
 
}

function digest_md5_parse_challenge($challenge) {
/* This function parses the challenge sent during DIGEST-MD5 authentication and
   returns an array. See the RFC for details on what's in the challenge string.
*/
  $challenge=base64_decode($challenge);
  while (isset($challenge)) {
    if ($challenge{0} == ',') { // First char is a comma, must not be 1st time through loop
      $challenge=substr($challenge,1);
    }
    $key=explode('=',$challenge,2);
    $challenge=$key[1];
    $key=$key[0];
    if ($challenge{0} == '"') {
      // We're in a quoted value
      // Drop the first quote, since we don't care about it
      $challenge=substr($challenge,1);
      // Now explode() to the next quote, which is the end of our value
      $val=explode('"',$challenge,2);
      $challenge=$val[1]; // The rest of the challenge, work on it in next iteration of loop
      $value=explode(',',$val[0]);
      // Now, for those quoted values that are only 1 piece..
      if (sizeof($value) == 1) {
        $value=$value[0];  // Convert to non-array
      }
    } else {
      // We're in a "simple" value - explode to next comma
      $val=explode(',',$challenge,2);
      if (isset($val[1])) {
	  	$challenge=$val[1];
	  } else {
	    unset($challenge);
	  }
      $value=$val[0];
    }
    $parsed["$key"]=$value;
  } // End of while loop
  return $parsed;
}

function hmac_md5($data, $key='') {
    // Creates a HMAC digest that can be used for auth purposes
    // See RFCs 2104, 2617, 2831
    // Uses mhash() extension if available
    if (extension_loaded('mhash')) {
      if ($key== '') {
        $mhash=mhash(MHASH_MD5,$data);
      } else {
        $mhash=mhash(MHASH_MD5,$data,$key);
      }
      return $mhash;
    }
    if (!$key) {
         return pack('H*',md5($data));
    }
    $key = str_pad($key,64,chr(0x00));
    if (strlen($key) > 64) {
        $key = pack("H*",md5($key));
    }
    $k_ipad =  $key ^ str_repeat(chr(0x36), 64) ;
    $k_opad =  $key ^ str_repeat(chr(0x5c), 64) ;
    /* Heh, let's get recursive. */
    $hmac=hmac_md5($k_opad . pack("H*",md5($k_ipad . $data)) );
    return $hmac;
}

?>
