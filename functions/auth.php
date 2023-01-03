<?php

/**
 * auth.php
 *
 * Contains functions used to do authentication.
 * 
 * Dependencies:
 *  functions/global.php
 *  functions/strings.php.
 *
 * @copyright 1999-2023 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */


/**
 * Detect whether user is logged in
 *
 * Function is similar to is_logged_in() function. If user is logged in, function
 * returns true. If user is not logged in or session is expired, function saves $_POST
 * and PAGE_NAME in session and returns false. POST information is saved in
 * 'session_expired_post' variable, PAGE_NAME is saved in 'session_expired_location'.
 *
 * This function optionally checks the referrer of this page request.  If the
 * administrator wants to impose a check that the referrer of this page request
 * is another page on the same domain (otherwise, the page request is likely
 * the result of a XSS or phishing attack), then they need to specify the
 * acceptable referrer domain in a variable named $check_referrer in
 * config/config.php (or the configuration tool) for which the value is
 * usually the same as the $domain setting (for example:
 *    $check_referrer = 'example.com';
 * However, in some cases (where proxy servers are in use, etc.), the
 * acceptable referrer might be different.  If $check_referrer is set to
 * "###DOMAIN###", then the current value of $domain is used (useful in
 * situations where $domain might change at runtime (when using the Login
 * Manager plugin to host multiple domains with one SquirrelMail installation,
 * for example)):
 *    $check_referrer = '###DOMAIN###';
 * NOTE HOWEVER, that referrer checks are not foolproof - they can be spoofed
 * by browsers, and some browsers intentionally don't send them, in which
 * case SquirrelMail silently ignores referrer checks.  
 *
 * Script that uses this function instead of is_logged_in() function, must handle user
 * level messages.
 * @return boolean
 * @since 1.5.1
 */
function sqauth_is_logged_in() {

    global $check_referrer, $domain;
    if (!sqgetGlobalVar('HTTP_REFERER', $referrer, SQ_SERVER)) $referrer = '';
    if ($check_referrer == '###DOMAIN###') $check_referrer = $domain;
    if (!empty($check_referrer)) {
        $ssl_check_referrer = 'https://' . $check_referrer;
        $plain_check_referrer = 'http://' . $check_referrer;
    }
    if (sqsession_is_registered('user_is_logged_in')
     && (!$check_referrer || empty($referrer)
      || ($check_referrer && !empty($referrer)
       && (strpos(strtolower($referrer), strtolower($plain_check_referrer)) === 0
        || strpos(strtolower($referrer), strtolower($ssl_check_referrer)) === 0)))) {
        return true;
    }

    //  First we store some information in the new session to prevent
    //  information-loss.
    $session_expired_post = $_POST;
    if (defined('PAGE_NAME'))
        $session_expired_location = PAGE_NAME;
    else
        $session_expired_location = '';

    if (!sqsession_is_registered('session_expired_post')) {
        sqsession_register($session_expired_post,'session_expired_post');
    }
    if (!sqsession_is_registered('session_expired_location')) {
        sqsession_register($session_expired_location,'session_expired_location');
    }

    session_write_close();

    return false;
}

/**
 * Reads and decodes stored user password information
 *
 * Direct access to password information is deprecated.
 * @return string password in plain text
 * @since 1.5.1
 */
function sqauth_read_password() {
    global $currentHookName;
    if ($currentHookName == 'login_verified') global $key;

    sqgetGlobalVar('key',         $key,       SQ_COOKIE);
    sqgetGlobalVar('onetimepad',  $onetimepad,SQ_SESSION);

    return OneTimePadDecrypt($key, $onetimepad);
}

/**
 * Saves or updates user password information
 *
 * This function is used to update the password information that
 * SquirrelMail stores in the existing PHP session. It does NOT 
 * modify the password stored in the authentication system used
 * by the IMAP server.
 *
 * This function must be called before any html output is started.
 * Direct access to password information is deprecated. The saved
 * password information is available only to the SquirrelMail script
 * that is called/executed AFTER the current one. If your script
 * needs access to the saved password after a sqauth_save_password()
 * call, use the returned OTP encrypted key.
 *
 * @param string $pass password
 *
 * @return string Password encrypted with OTP. In case the script
 *                wants to access the password information before
 *                the end of its execution.
 *
 * @since 1.5.1
 *
 */
function sqauth_save_password($pass) {
    sqgetGlobalVar('base_uri',    $base_uri,   SQ_SESSION);

    $onetimepad = OneTimePadCreate(strlen($pass));
    sqsession_register($onetimepad,'onetimepad');
    $key = OneTimePadEncrypt($pass, $onetimepad);
    sqsetcookie('key', $key, false, $base_uri);
    return $key;
}

/**
 * Determine if an algorithm is supported by hash() and hash_hmac()
 *
 * @param string $algo Algorithm to find.
 *
 * @return string Functional $algo as used by hash() and hash_hmac()
 *      or boolean FALSE
 *
 * @since 1.5.2
 */

function scram_supports($algo) {
 $HASHs = hash_algos();
 if (check_php_version(7,2)) {
  $HMACs = hash_hmac_algos();
  $HASHs = array_values(array_intersect($HASHs, $HMACs));
 }
 $fAlgo = strtolower($algo);
 if (in_array($fAlgo, $HASHs))
  return $fAlgo;
 $fAlgo = str_replace('-', '', $fAlgo);
 if (in_array($fAlgo, $HASHs))
  return $fAlgo;
 return false;
}

/**
 * Build client nonce for SCRAM (See RFC 5802 for details)
 *
 * @return string A set of twenty random printable ASCII characters
 *
 * @since 1.5.2
 */
function scram_nonce () {
    // All printable ASCII characters except commas are OK
    // (For simplicity, we're just going to use letters and numbers, though)
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $max = strlen($chars) - 1;
    $nonce = '';
    for($i = 0; $i < 20; $i++) {
        $rndChr = random_int(0, $max);
        $nonce.= $chars[$rndChr];
    }
    return $nonce;
}

/**
 * Build client request for SCRAM (See RFC 5802 for details)
 *
 * @param string $username User ID
 * @param string $cbf Channel Binding Flag ('n', 'y', or 'p=tls-unique'/'p=tls-server-end-point')
 * @param string $nonce Client's random nonce data
 *
 * @return string The response to be sent to the server (base64 encoded)
 *
 * @since 1.5.2
 */
function scram_request ($username,$cbf,$nonce) {
    return base64_encode($cbf.',,n='.$username.',r='.$nonce);
}

/**
 * Parse SCRAM challenge.
 * This function parses the challenge sent during SCRAM authentication and
 * returns an array. See the RFC for details on what's in the challenge string.
 *
 * @param string $challenge SCRAM Challenge
 * @param string $nonce Client's random nonce data
 *
 * @return array SCRAM challenge decoded data
 *      or boolean FALSE
 *
 * @since 1.5.2
 */
function scram_parse_challenge ($challenge,$nonce) {
    $chall = base64_decode($challenge, true);
    if ($chall === false) {
        // The challenge must be base64 encoded
        return false;
    }
    // Chall should now be r=NONCE,s=SALT,i=ITER
    $sReq = explode(',', $chall);
    $serNonce = '';
    $serSalt  = '';
    $serIter  = 0;
    for($i = 0; $i < count($sReq); $i++) {
        switch(substr($sReq[$i], 0, 2)) {
            case 'r=':
                $serNonce = substr($sReq[$i], 2);
                break;
            case 's=':
                $serSalt = substr($sReq[$i], 2);
                break;
            case 'i=':
                $serIter = substr($sReq[$i], 2);
                break;
        }
    }
    if (strlen($serNonce) <= strlen($nonce)) {
        //the server 'r' value must be bigger than the client 'r' value
        return false;
    }
    if (substr($serNonce, 0, strlen($nonce)) !== $nonce) {
        // The server 'r' value must begin with the client 'r' value
        return false;
    }
    if (is_numeric($serIter)) {
        $serIter = intval($serIter);
    } else {
        // The iteration value must be a number
        return false;
    }
    $serSaltV = base64_decode($serSalt, true);
    if ($serSaltV === false) {
        // The salt must be base64-encoded
        return false;
    }
    $parsed = array();
    $parsed['r'] = $serNonce;
    $parsed['s'] = $serSaltV;
    $parsed['i'] = $serIter;
    return $parsed;
}

/**
 * Build SCRAM response to challenge.
 * This function hashes the heck out of the password and all previous communications
 * to create a proof value which is then sent to the server as authentication.
 *
 * @param string $alg Hash algorithm to use ('sha1' or 'sha256')
 * @param string $username User ID
 * @param string $cbf Channel Binding Flag ('n', 'y', or 'p=tls-unique'/'p=tls-server-end-point')
 * @param string $cli_nonce Client's random nonce data
 * @param string $ser_nonce Client + Server's random nonce data
 * @param string $password User password supplied by User
 * @param string $salt Raw binary salt data, supplied by the server challenge
 * @param string $iter PBKDF2 iterations, supplied by the server challenge
 *
 * @return string The response to be sent to the server (base64 encoded)
 *
 * @since 1.5.2
 */
function scram_response ($alg,$username,$cbf,$cli_nonce,$ser_nonce,$password,$salt,$iter) {
    // salt and hash password
    $salted_pass = hash_pbkdf2($alg, $password, $salt, $iter, 0, true);
    $cli_hash = hash_hmac($alg, 'Client Key', $salted_pass, true);
    $cli_key = hash($alg, $cli_hash, true);

    $c = base64_encode($cbf.',,');

    //generate unproofed communications
    $cli_request = 'n='.$username.',r='.$cli_nonce;
    $ser_challenge = 'r='.$ser_nonce.',s='.base64_encode($salt).',i='.$iter;
    $cli_response_unp = 'c='.$c.',r='.$ser_nonce;
    $comm_unp = $cli_request.','.$ser_challenge.','.$cli_response_unp;

    //hash unproofed communications
    $cli_sig = hash_hmac($alg, $comm_unp, $cli_key, true);
    $cli_proof = $cli_hash ^ $cli_sig;

    //generate proofed response
    $cli_response = $cli_response_unp.',p='.base64_encode($cli_proof);

    return base64_encode($cli_response);
}

/**
 * Verify SCRAM server response.
 * The final step in SCRAM is to make sure the server isn't just faking validation.
 * This is done by hashing the unproofed communications with a 'Server Key'
 * version of the hashed password, and comparing it with the server's final SCRAM message.
 *
 * @param string $alg Hash algorithm to use ('sha1' or 'sha256')
 * @param string $username User ID
 * @param string $cbf Channel Binding Flag ('n', 'y', or 'p=tls-unique'/'p=tls-server-end-point')
 * @param string $cli_nonce Client's random nonce data
 * @param string $ser_nonce Client + Server's random nonce data
 * @param string $password User password supplied by User
 * @param string $salt Raw binary salt data, supplied by the server challenge
 * @param string $iter PBKDF2 iterations, supplied by the server challenge
 * @param string $proof The server's final SCRAM message (base64 encoded)
 *
 * @return boolean Success or failure
 *
 * @since 1.5.2
 */
function scram_verify ($alg,$username,$cbf,$cli_nonce,$ser_nonce,$password,$salt,$iter,$proof) {
    $proof = base64_decode($proof, true);
    if ($proof === false) {
        // The proof must be base64 encoded
        return false;
    }
    if (substr($proof, 0, 2) !== 'v=') {
        // The proof was not provided correctly
        return false;
    }
    $proof = substr($proof, 2);
    $proof = base64_decode($proof, true);
    if ($proof === false) {
        // The proof v value must be base64 encoded
        return false;
    }
    // salt and hash password
    $salted_pass = hash_pbkdf2($alg, $password, $salt, $iter, 0, true);
    $cli_hash = hash_hmac($alg, 'Client Key', $salted_pass, true);
    $cli_key = hash($alg, $cli_hash, true);

    $c = base64_encode($cbf.',,');

    //generate unproofed communications
    $cli_request = 'n='.$username.',r='.$cli_nonce;
    $ser_challenge = 'r='.$ser_nonce.',s='.base64_encode($salt).',i='.$iter;
    $cli_response_unp = 'c='.$c.',r='.$ser_nonce;
    $comm_unp = $cli_request.','.$ser_challenge.','.$cli_response_unp;

    //hash for server
    $ser_hash = hash_hmac($alg, 'Server Key', $salted_pass, true);
    $ser_proof = hash_hmac($alg, $comm_unp, $ser_hash, true);
    return $ser_proof === $proof;
}

/**
 * Given the challenge from the server, supply the response using cram-md5 (See
 * RFC 2195 for details)
 *
 * @param string $username User ID
 * @param string $password User password supplied by User
 * @param string $challenge The challenge supplied by the server
 * @return string The response to be sent to the IMAP server
 * @since 1.4.0
 */
function cram_md5_response ($username,$password,$challenge) {
    $challenge=base64_decode($challenge);
    $hash=bin2hex(hmac_md5($challenge,$password));
    $response=base64_encode($username . " " . $hash) . "\r\n";
    return $response;
}

/**
 * Return Digest-MD5 response.
 * Given the challenge from the server, calculate and return the
 * response-string for digest-md5 authentication.  (See RFC 2831 for more
 * details)
 *
 * @param string $username User ID
 * @param string $password User password supplied by User
 * @param string $challenge The challenge supplied by the server
 * @param string $service The service name, usually 'imap'; it is used to
 *   define the digest-uri.
 * @param string $host The host name, usually the server's FQDN; it is used to
 *   define the digest-uri.
 * @param string $authz Authorization ID (since 1.5.2)
 * @return string The response to be sent to the IMAP server
 * @since 1.4.0
 */
function digest_md5_response ($username,$password,$challenge,$service,$host,$authz='') {
    $result=digest_md5_parse_challenge($challenge);
    //FIXME we should check that $result contains the expected values that we use below

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
    if(!empty($authz)) {
        $A1 .= ":" . utf8_encode($authz);
    }
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
    if(!empty($authz)) {
        $reply .= ',authzid=' . $authz;
    }
    $reply = base64_encode($reply);
    return $reply . "\r\n";

}

/**
 * Parse Digest-MD5 challenge.
 * This function parses the challenge sent during DIGEST-MD5 authentication and
 * returns an array. See the RFC for details on what's in the challenge string.
 *
 * @param string $challenge Digest-MD5 Challenge
 * @return array Digest-MD5 challenge decoded data
 * @since 1.4.0
 */
function digest_md5_parse_challenge($challenge) {
    $challenge=base64_decode($challenge);
    $parsed = array();
    while (!empty($challenge)) {
        if ($challenge[0] == ',') { // First char is a comma, must not be 1st time through loop
            $challenge=substr($challenge,1);
        }
        $key=explode('=',$challenge,2);
        $challenge=$key[1];
        $key=$key[0];
        if ($challenge[0] == '"') {
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

/**
  * Creates a HMAC digest that can be used for authentication purposes
  * See RFCs 2104, 2617, 2831
  *
  * Uses PHP's Hash extension if available (enabled by default in PHP
  * 5.1.2+ - see http://www.php.net/manual/en/hash.requirements.php
  * or, if installed on earlier PHP versions, the PECL hash module -
  * see http://pecl.php.net/package/hash
  *
  * Otherwise, will attempt to use the Mhash extension - see
  * http://www.php.net/manual/en/mhash.requirements.php
  *
  * Finally, a fall-back custom implementation is used if none of
  * the above are available.
  *
  * @param string $data The data to be encoded/hashed
  * @param string $key The (shared) secret key that will be used
  *                    to build the keyed hash.  This argument is
  *                    technically optional, but only for internal
  *                    use (when the custom hash implementation is
  *                    being used) - external callers should always
  *                    specify a value for this argument.
  *
  * @return string The HMAC-MD5 digest string
  * @since 1.4.0
  *
  */
function hmac_md5($data, $key='') {

    // use PHP's native Hash extension if possible
    //
    if (function_exists('hash_hmac'))
        return pack('H*', hash_hmac('md5', $data, $key));


    // otherwise, use (obsolete) mhash extension if available
    //
    if (extension_loaded('mhash')) {

        if ($key == '')
            $mhash = mhash(MHASH_MD5, $data);
        else
            $mhash = mhash(MHASH_MD5, $data, $key);

        return $mhash;
    }


    // or, our own implementation...
    //
    if (!$key)
        return pack('H*', md5($data));

    $key = str_pad($key, 64, chr(0x00));

    if (strlen($key) > 64)
        $key = pack("H*", md5($key));

    $k_ipad = $key ^ str_repeat(chr(0x36), 64);
    $k_opad = $key ^ str_repeat(chr(0x5c), 64);

    $hmac = hmac_md5($k_opad . pack('H*', md5($k_ipad . $data)));

    return $hmac;

}

/**
 * Fillin user and password based on SMTP auth settings.
 *
 * @param string $user Reference to SMTP username
 * @param string $pass Reference to SMTP password (unencrypted)
 * @since 1.4.11
 */
function get_smtp_user(&$user, &$pass) {
    global $username, $smtp_auth_mech,
           $smtp_sitewide_user, $smtp_sitewide_pass;

    if ($smtp_auth_mech == 'none') {
        $user = '';
        $pass = '';
    } elseif ( isset($smtp_sitewide_user) && isset($smtp_sitewide_pass) &&
               !empty($smtp_sitewide_user)) {
        $user = $smtp_sitewide_user;
        $pass = $smtp_sitewide_pass;
    } else {
        $user = $username;
        $pass = sqauth_read_password();
    }

    // plugin authors note: override $user or $pass by
    // directly changing the arguments array contents 
    // in your plugin e.g., $args[0] = 'new_username';
    //
    // NOTE: there is another hook in class/deliver/Deliver_SMTP.class.php
    // called "smtp_authenticate" that allows a plugin to run its own
    // custom authentication routine - this hook here is thus slightly
    // mis-named but is too old to change.  Be careful that you do not
    // confuse your hook names.
    //
    $temp = array(&$user, &$pass);
    do_hook('smtp_auth', $temp);
}
