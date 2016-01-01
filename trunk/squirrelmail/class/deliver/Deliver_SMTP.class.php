<?php

/**
 * Deliver_SMTP.class.php
 *
 * SMTP delivery backend for the Deliver class.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** @ignore */
if (!defined('SM_PATH')) define('SM_PATH','../../');

/** This of course depends upon Deliver */
include_once(SM_PATH . 'class/deliver/Deliver.class.php');

/**
 * Deliver messages using SMTP
 * @package squirrelmail
 */
class Deliver_SMTP extends Deliver {
    /**
     * Array keys are uppercased ehlo keywords
     * array key values are ehlo params. If ehlo-param contains space, it is splitted into array. 
     * @var array ehlo
     * @since 1.5.1
     */
    var $ehlo = array();
    /**
     * @var string domain
     * @since 1.5.1
     */
    var $domain = '';
    /**
     * SMTP STARTTLS rfc: "Both the client and the server MUST know if there 
     * is a TLS session active."
     * Variable should be set to true, when encryption is turned on.
     * @var boolean
     * @since 1.5.1 
     */
    var $tls_enabled = false;
    /**
     * Private var
     * var stream $stream
     * @todo don't pass stream resource in class method arguments.
     */
    //var $stream = false;
    /** @var string delivery error message */
    var $dlv_msg = '';
    /** @var integer delivery error number from server */
    var $dlv_ret_nr = '';
    /** @var string delivery error message from server */
    var $dlv_server_msg = '';

    function preWriteToStream(&$s) {
        if ($s) {
            if ($s{0} == '.')   $s = '.' . $s;
            $s = str_replace("\n.","\n..",$s);
        }
    }

    function initStream($message, $domain, $length=0, $host='', $port='', $user='', $pass='', $authpop=false, $pop_host='', $stream_options=array()) {
        global $use_smtp_tls,$smtp_auth_mech;

        if ($authpop) {
            $this->authPop($pop_host, '', $user, $pass);
        }

        $rfc822_header = $message->rfc822_header;

        $from = $rfc822_header->from[0];
        $to =   $rfc822_header->to;
        $cc =   $rfc822_header->cc;
        $bcc =  $rfc822_header->bcc;
        $content_type  = $rfc822_header->content_type;

        // MAIL FROM: <from address> MUST be empty in cae of MDN (RFC2298)
        if ($content_type->type0 == 'multipart' &&
            $content_type->type1 == 'report' &&
            isset($content_type->properties['report-type']) &&
            $content_type->properties['report-type']=='disposition-notification') {
            // reinitialize the from object because otherwise the from header somehow
            // is affected. This $from var is used for smtp command MAIL FROM which
            // is not the same as what we put in the rfc822 header.
            $from = new AddressStructure();
            $from->host = '';
            $from->mailbox = '';
        }

        // NB: Using "ssl://" ensures the highest possible TLS version
        // will be negotiated with the server (whereas "tls://" only
        // uses TLS version 1.0)
        //
        if ($use_smtp_tls == 1) {
            if ((check_php_version(4,3)) && (extension_loaded('openssl'))) {
                if (function_exists('stream_socket_client')) {
                    $server_address = 'ssl://' . $host . ':' . $port;
                    $ssl_context = @stream_context_create($stream_options);
                    $connect_timeout = ini_get('default_socket_timeout');
                    // null timeout is broken
                    if ($connect_timeout == 0)
                        $connect_timeout = 30;
                    $stream = @stream_socket_client($server_address, $errorNumber, $errorString, $connect_timeout, STREAM_CLIENT_CONNECT, $ssl_context);
                } else {
                    $stream = @fsockopen('ssl://' . $host, $port, $errorNumber, $errorString);
                }
                $this->tls_enabled = true;
            } else {
                /**
                 * don't connect to server when user asks for smtps and 
                 * PHP does not support it.
                 */
                $errorNumber = '';
                $errorString = _("Secure SMTP (TLS) is enabled in SquirrelMail configuration, but used PHP version does not support it.");
            }
        } else {
            $stream = @fsockopen($host, $port, $errorNumber, $errorString);
        }

        if (!$stream) {
            // reset tls state var to default value, if connection fails
            $this->tls_enabled = false;
            // set error messages
            $this->dlv_msg = $errorString;
            $this->dlv_ret_nr = $errorNumber;
            $this->dlv_server_msg = _("Can't open SMTP stream.");
            return(0);
        }
        // get server greeting
        $tmp = fgets($stream, 1024);
        if ($this->errorCheck($tmp, $stream)) {
            return(0);
        }

        /*
         * If $_SERVER['HTTP_HOST'] is set, use that in our HELO to the SMTP
         * server.  This should fix the DNS issues some people have had
         */
        if (sqgetGlobalVar('HTTP_HOST', $HTTP_HOST, SQ_SERVER)) { // HTTP_HOST is set
            // optionally trim off port number
            if($p = strrpos($HTTP_HOST, ':')) {
                $HTTP_HOST = substr($HTTP_HOST, 0, $p);
            }
            $helohost = $HTTP_HOST;
        } else { // For some reason, HTTP_HOST is not set - revert to old behavior
            $helohost = $domain;
        }

        // if the host is an IPv4 address, enclose it in brackets
        //
        if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $helohost))
            $helohost = '[' . $helohost . ']';

        $hook_result = do_hook('smtp_helo_override', $helohost);
        if (!empty($hook_result)) $helohost = $hook_result;

        /* Lets introduce ourselves */
        fputs($stream, "EHLO $helohost\r\n");
        // Read ehlo response
        $tmp = $this->parse_ehlo_response($stream);
        if ($this->errorCheck($tmp,$stream)) {
            // fall back to HELO if EHLO is not supported (error 5xx)
            if ($this->dlv_ret_nr{0} == '5') {
                fputs($stream, "HELO $helohost\r\n");
                $tmp = fgets($stream,1024);
                if ($this->errorCheck($tmp,$stream)) {
                    return(0);
                }
            } else {
                return(0);
            }
        }

        /**
         * Implementing SMTP STARTTLS (rfc2487) in php 5.1.0+
         * http://www.php.net/stream-socket-enable-crypto
         */
        if ($use_smtp_tls === 2) {
            if (function_exists('stream_socket_enable_crypto')) {
                // don't try starting tls, when client thinks that it is already active
                if ($this->tls_enabled) {
                    $this->dlv_msg = _("TLS session is already activated.");
                    return 0;
                } elseif (!array_key_exists('STARTTLS',$this->ehlo)) {
                    // check for starttls in ehlo response
                    $this->dlv_msg = _("SMTP STARTTLS is enabled in SquirrelMail configuration, but used SMTP server does not support it");
                    return 0;
                }

                // issue starttls command
                fputs($stream, "STARTTLS\r\n");
                // get response
                $tmp = fgets($stream,1024);
                if ($this->errorCheck($tmp,$stream)) {
                    return 0;
                }

                // start crypto on connection. suppress function errors.
                if (@stream_socket_enable_crypto($stream,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    // starttls was successful (rfc2487 5.2 Result of the STARTTLS Command)
                    // get new EHLO response
                    fputs($stream, "EHLO $helohost\r\n");
                    // Read ehlo response
                    $tmp = $this->parse_ehlo_response($stream);
                    if ($this->errorCheck($tmp,$stream)) {
                        // don't revert to helo here. server must support ESMTP
                        return 0;
                    }
                    // set information about started tls
                    $this->tls_enabled = true;
                } else {
                    /**
                     * stream_socket_enable_crypto() call failed.
                     */
                    $this->dlv_msg = _("Unable to start TLS.");
                    return 0;
                    // Bug: can't get error message. See comments in sqimap_create_stream().
                }
            } else {
                // php install does not support stream_socket_enable_crypto() function
                $this->dlv_msg = _("SMTP STARTTLS is enabled in SquirrelMail configuration, but used PHP version does not support functions that allow to enable encryption on open socket.");
                return 0;
            }
        }

        // FIXME: check ehlo response before using authentication

        // Try authentication by a plugin
        //
        // NOTE: there is another hook in functions/auth.php called "smtp_auth" 
        // that allows a plugin to specify a different set of login credentials 
        // (so is slightly mis-named, but is too old to change), so be careful 
        // that you do not confuse your hook names.
        //
        $smtp_auth_args = array(
            'auth_mech' => $smtp_auth_mech,
            'user' => $user,
            'pass' => $pass,
            'host' => $host,
            'port' => $port,
            'stream' => $stream,
        );
        if (boolean_hook_function('smtp_authenticate', $smtp_auth_args, 1)) {
            // authentication succeeded
        } else if (( $smtp_auth_mech == 'cram-md5') or ( $smtp_auth_mech == 'digest-md5' )) {
            // Doing some form of non-plain auth
            if ($smtp_auth_mech == 'cram-md5') {
                fputs($stream, "AUTH CRAM-MD5\r\n");
            } elseif ($smtp_auth_mech == 'digest-md5') {
                fputs($stream, "AUTH DIGEST-MD5\r\n");
            }

            $tmp = fgets($stream,1024);

            if ($this->errorCheck($tmp,$stream)) {
                return(0);
            }

            // At this point, $tmp should hold "334 <challenge string>"
            $chall = substr($tmp,4);
            // Depending on mechanism, generate response string
            if ($smtp_auth_mech == 'cram-md5') {
                $response = cram_md5_response($user,$pass,$chall);
            } elseif ($smtp_auth_mech == 'digest-md5') {
                $response = digest_md5_response($user,$pass,$chall,'smtp',$host);
            }
            fputs($stream, $response);

            // Let's see what the server had to say about that
            $tmp = fgets($stream,1024);
            if ($this->errorCheck($tmp,$stream)) {
                return(0);
            }

            // CRAM-MD5 is done at this point.  If DIGEST-MD5, there's a bit more to go
            if ($smtp_auth_mech == 'digest-md5') {
                // $tmp contains rspauth, but I don't store that yet. (No need yet)
                fputs($stream,"\r\n");
                $tmp = fgets($stream,1024);

                if ($this->errorCheck($tmp,$stream)) {
                return(0);
                }
            }
        // CRAM-MD5 and DIGEST-MD5 code ends here
        } elseif ($smtp_auth_mech == 'none') {
        // No auth at all, just send helo and then send the mail
        // We already said hi earlier, nothing more is needed.
        } elseif ($smtp_auth_mech == 'login') {
            // The LOGIN method
            fputs($stream, "AUTH LOGIN\r\n");
            $tmp = fgets($stream, 1024);

            if ($this->errorCheck($tmp, $stream)) {
                return(0);
            }
            fputs($stream, base64_encode ($user) . "\r\n");
            $tmp = fgets($stream, 1024);
            if ($this->errorCheck($tmp, $stream)) {
                return(0);
            }

            fputs($stream, base64_encode($pass) . "\r\n");
            $tmp = fgets($stream, 1024);
            if ($this->errorCheck($tmp, $stream)) {
                return(0);
            }
        } elseif ($smtp_auth_mech == "plain") {
            /* SASL Plain */
            $auth = base64_encode("$user\0$user\0$pass");

            $query = "AUTH PLAIN\r\n";
            fputs($stream, $query);
            $read=fgets($stream, 1024);

            if (substr($read,0,3) == '334') { // OK so far..
                fputs($stream, "$auth\r\n");
                $read = fgets($stream, 1024);
            }

            $results=explode(" ",$read,3);
            $response=$results[1];
            $message=$results[2];
        } else {
            /* Right here, they've reached an unsupported auth mechanism.
            This is the ugliest hack I've ever done, but it'll do till I can fix
            things up better tomorrow.  So tired... */
            if ($this->errorCheck("535 Unable to use this auth type",$stream)) {
                return(0);
            }
        }

        /* Ok, who is sending the message? */
        $fromaddress = (strlen($from->mailbox) && $from->host) ?
            $from->mailbox.'@'.$from->host : '';
        fputs($stream, 'MAIL FROM:<'.$fromaddress.">\r\n");
        $tmp = fgets($stream, 1024);
        if ($this->errorCheck($tmp, $stream)) {
            return(0);
        }

        /* send who the recipients are */
        for ($i = 0, $cnt = count($to); $i < $cnt; $i++) {
            if (!$to[$i]->host) $to[$i]->host = $domain;
            if (strlen($to[$i]->mailbox)) {
                fputs($stream, 'RCPT TO:<'.$to[$i]->mailbox.'@'.$to[$i]->host.">\r\n");
                $tmp = fgets($stream, 1024);
                if ($this->errorCheck($tmp, $stream)) {
                    return(0);
                }
            }
        }

        for ($i = 0, $cnt = count($cc); $i < $cnt; $i++) {
            if (!$cc[$i]->host) $cc[$i]->host = $domain;
            if (strlen($cc[$i]->mailbox)) {
                fputs($stream, 'RCPT TO:<'.$cc[$i]->mailbox.'@'.$cc[$i]->host.">\r\n");
                $tmp = fgets($stream, 1024);
                if ($this->errorCheck($tmp, $stream)) {
                    return(0);
                }
            }
        }

        for ($i = 0, $cnt = count($bcc); $i < $cnt; $i++) {
            if (!$bcc[$i]->host) $bcc[$i]->host = $domain;
            if (strlen($bcc[$i]->mailbox)) {
                fputs($stream, 'RCPT TO:<'.$bcc[$i]->mailbox.'@'.$bcc[$i]->host.">\r\n");
                $tmp = fgets($stream, 1024);
                if ($this->errorCheck($tmp, $stream)) {
                    return(0);
                }
            }
        }
        /* Lets start sending the actual message */
        fputs($stream, "DATA\r\n");
        $tmp = fgets($stream, 1024);
        if ($this->errorCheck($tmp, $stream)) {
                return(0);
        }
        return $stream;
    }

    function finalizeStream($stream) {
        fputs($stream, "\r\n.\r\n"); /* end the DATA part */
        $tmp = fgets($stream, 1024);
        $this->errorCheck($tmp, $stream);
        if ($this->dlv_ret_nr != 250) {
                return(0);
        }
        fputs($stream, "QUIT\r\n"); /* log off */
        fclose($stream);
        return true;
    }

    /* check if an SMTP reply is an error and set an error message) */
    function errorCheck($line, $smtpConnection) {

        $err_num = substr($line, 0, 3);
        $this->dlv_ret_nr = $err_num;
        $server_msg = substr($line, 4);

        while(substr($line, 0, 4) == ($err_num.'-')) {
            $line = fgets($smtpConnection, 1024);
            $server_msg .= substr($line, 4);
        }

        if ( ((int) $err_num{0}) < 4) {
            return false;
        }

        switch ($err_num) {
        case '421': $message = _("Service not available, closing channel");
            break;
        case '432': $message = _("A password transition is needed");
            break;
        case '450': $message = _("Requested mail action not taken: mailbox unavailable");
            break;
        case '451': $message = _("Requested action aborted: error in processing");
            break;
        case '452': $message = _("Requested action not taken: insufficient system storage");
            break;
        case '454': $message = _("Temporary authentication failure");
            break;
        case '500': $message = _("Syntax error; command not recognized");
            break;
        case '501': $message = _("Syntax error in parameters or arguments");
            break;
        case '502': $message = _("Command not implemented");
            break;
        case '503': $message = _("Bad sequence of commands");
            break;
        case '504': $message = _("Command parameter not implemented");
            break;
        case '530': $message = _("Authentication required");
            break;
        case '534': $message = _("Authentication mechanism is too weak");
            break;
        case '535': $message = _("Authentication failed");
            break;
        case '538': $message = _("Encryption required for requested authentication mechanism");
            break;
        case '550': $message = _("Requested action not taken: mailbox unavailable");
            break;
        case '551': $message = _("User not local; please try forwarding");
            break;
        case '552': $message = _("Requested mail action aborted: exceeding storage allocation");
            break;
        case '553': $message = _("Requested action not taken: mailbox name not allowed");
            break;
        case '554': $message = _("Transaction failed");
            break;
        default:    $message = _("Unknown response");
            break;
        }

        $this->dlv_msg = $message;
        $this->dlv_server_msg = $server_msg;

        return true;
    }

    function authPop($pop_server='', $pop_port='', $user, $pass) {
        if (!$pop_port) {
            $pop_port = 110;
        }
        if (!$pop_server) {
            $pop_server = 'localhost';
        }
        $popConnection = @fsockopen($pop_server, $pop_port, $err_no, $err_str);
        if (!$popConnection) {
            error_log("Error connecting to POP Server ($pop_server:$pop_port)"
                . " $err_no : $err_str");
            return false;
        } else {
            $tmp = fgets($popConnection, 1024); /* banner */
            if (substr($tmp, 0, 3) != '+OK') {
                return false;
            }
            fputs($popConnection, "USER $user\r\n");
            $tmp = fgets($popConnection, 1024);
            if (substr($tmp, 0, 3) != '+OK') {
                return false;
            }
            fputs($popConnection, 'PASS ' . $pass . "\r\n");
            $tmp = fgets($popConnection, 1024);
            if (substr($tmp, 0, 3) != '+OK') {
                return false;
            }
            fputs($popConnection, "QUIT\r\n"); /* log off */
            fclose($popConnection);
        }
        return true;
    }

    /**
     * Parses ESMTP EHLO response (rfc1869)
     *
     * Reads SMTP response to EHLO command and fills class variables 
     * (ehlo array and domain string). Returns last line.
     * @param stream $stream smtp connection stream.
     * @return string last ehlo line
     * @since 1.5.1
     */
    function parse_ehlo_response($stream) {
        // don't cache ehlo information
        $this->ehlo=array();
        $ret = '';
        $firstline = true;
        /**
         * ehlo mailclient.example.org
         * 250-mail.example.org
         * 250-PIPELINING
         * 250-SIZE 52428800
         * 250-DATAZ
         * 250-STARTTLS
         * 250-AUTH LOGIN PLAIN
         * 250 8BITMIME
         */
        while ($line=fgets($stream, 1024)){
            // match[1] = first symbol after 250
            // match[2] = domain or ehlo-keyword
            // match[3] = greeting or ehlo-param
            // match space after keyword in ehlo-keyword CR LF
            if (preg_match("/^250(-|\s)(\S*)\s+(\S.*)\r\n/",$line,$match)||
                preg_match("/^250(-|\s)(\S*)\s*\r\n/",$line,$match)) {
                if ($firstline) {
                    // first ehlo line (250[-\ ]domain SP greeting)
                    $this->domain = $match[2];
                    $firstline=false;
                } elseif (!isset($match[3])) {
                    // simple one word extension
                    $this->ehlo[strtoupper($match[2])]='';
                } elseif (!preg_match("/\s/",trim($match[3]))) {
                    // extension with one option
                    // yes, I know about ctype extension. no, i don't want to depend on it
                    $this->ehlo[strtoupper($match[2])]=trim($match[3]);
                } else {
                    // ehlo-param with spaces
                    $this->ehlo[strtoupper($match[2])]=explode(' ',trim($match[3]));
                }
                if ($match[1]==' ') {
                    // stop while cycle, if we reach last 250 line
                    $ret = $line;
                    break;
                }
            } else {
                // this is not 250 response
                $ret = $line;
                break;
            }
        }
        return $ret;
    }
}
