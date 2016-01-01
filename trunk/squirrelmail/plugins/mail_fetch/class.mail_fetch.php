<?php
/**
 * POP client class
 *
 * Class depends on PHP pcre extension and fsockopen() function. Some features
 * might require PHP 4.3.0 with OpenSSL or PHP 5.1.0+. Class checks those extra 
 * dependencies internally, if used function needs it.
 * @copyright 2006-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage mail_fetch
 * @link http://www.ietf.org/rfc/rfc1939.txt RFC1939
 * @link http://www.ietf.org/rfc/rfc2449.txt POP3EXT
 * @link http://www.ietf.org/rfc/rfc2595.txt STARTTLS
 * @link http://www.ietf.org/rfc/rfc1734.txt AUTH command (unsupported)
 */

/**
 * POP3 client class
 *
 * POP connection is opened when class is constructed. All command_* methods
 * execute specific POP commands on server. Most of other methods should be 
 * used only internally. Only login() method is public. If command returns 
 * mixed content and you expect message text, ids or something else, make sure
 * that it is not boolean false.
 *
 * Basic use:
 * 1. create object with connection params, see mail_fetch method.
 * 2. check error buffer
 * 3. login($username,$password) - true = login successful, false = login error.
 * 4. command_stat() - get number of messages
 * 5. command_list() - get message ids, use command_uidl(), if you implement 
 * 'keep mess on server' functions. Make sure that you handle possible UIDL 
 * command errors.
 * 6. command_retr($some_message_id) - get message contents
 * 7. command_dele($some_message_id) - mark message for deletion
 * 8. command_quit() - close connection. You must close connection in order 
 *    to delete messages and remove mailbox lock.
 * @package plugins
 * @subpackage mail_fetch
 */
class mail_fetch {
    /**
     * Server name
     * @var string
     */
    var $host = '';

    /**
     * POP connection port.
     * Defaults to 110 on plain text connections and to 995 on TLS
     * @var integer
     */
    var $port = 0;

    /**
     * Connection type
     * 0 - plain text (default)
     * 1 - tls (php 4.3 and openssl extension requirement)
     * 2 - stls (stream_socket_enable_crypto() requirement. PHP 5.1.0, POP3
     *     server with POP3EXT and STLS support)
     * @var integer
     */
    var $tls = 0;

    /**
     * Authentication type
     *
     * Bitwise variable. If variable covers more than one authentication method,
     * login() tries to use all of them until first successful auth.
     * 1 - user/pass (rfc1939, default)
     * 2 - apop (rfc1939, timestamp must be present in greeting)
     * 3 - apop or user/pass
     * @var integer
     */
    var $auth = 1;

    /**
     * Connection timeout
     * @var integer
     */
    var $timeout = 60;

    /**
     * Connection resource
     * @var stream
     */
    var $conn = false;

    /**
     * Server greeting
     * @var string
     */
    var $greeting = '';

    /**
     * Timestamp (with <> or empty string)
     * @var string
     */
    var $timestamp = '';

    /**
     * Capabilities (POP3EXT capa)
     * @var array
     */
    var $capabilities = array();

    /**
     * Error message buffer
     * @var string
     */
    var $error = '';

    /**
     * Response buffer
     *
     * Variable is used to store last positive POP server response 
     * checked in check_response() method. Used internally to handle
     * mixed single and multiline command responses.
     * @var string
     */
    var $response = '';

    /**
     * Constructor function
     * 
     * parameter array keys
     * 'host' - required string, address of server. ip or fqn
     * 'port' - optional integer, port of server.
     * 'tls' - optional integer, connection type
     * 'timeout' - optional integer, connection timeout
     * 'auth' - optional integer, used authentication mechanism.
     * See description of class properties
     * @param array $aParams connection params
     */
    function mail_fetch($aParams=array()) {
        // hostname
        if (isset($aParams['host'])) {
            $this->host = $aParams['host'];
        } else {
            return $this->set_error('Server name is not set');
        }
        // tls
        if (isset($aParams['tls'])) {
            $this->tls = (int) $aParams['tls'];
        }
        // port
        if (isset($aParams['port'])) {
            $this->port = (int) $aParams['port'];
        }
        // set default ports
        if ($this->port == 0) {
            if ($this->tls===1) {
                // pops
                $this->port = 995;
            } else {
                // pop3
                $this->port = 110;
            }
        }
        // timeout
        if (isset($aParams['timeout'])) {
            $this->timeout = (int) $aParams['timeout'];
        }
        // authentication mech
        if (isset($aParams['auth'])) {
            $this->auth = (int) $aParams['auth'];
        }

        // open connection
        $this->open();
    }

    // Generic methods to handle connection and login operations.

    /**
     * Opens pop connection
     *
     * Command handles TLS and STLS connection differences and fills capabilities 
     * array with RFC2449 CAPA data.
     * @return boolean
     */
    function open() {
        if ($this->conn) {
            return true;
        }

        if ($this->tls===1) {
            if (! $this->check_php_version(4,3) || ! extension_loaded('openssl')) {
                return $this->set_error('Used PHP version does not support functions required for POP TLS.');
            }
            $target = 'tls://' . $this->host;
        } else {
            $target = $this->host;
        }

        $this->conn = @fsockopen($target, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->conn) {
            $error = sprintf('Error %d: ',$errno) . $errstr;
            return $this->set_error($error);
        }

        // read greeting
        $this->greeting = trim(fgets($this->conn));

        // check greeting for errors and extract timestamp
        if (preg_match('/^-ERR (.+)/',$this->greeting,$matches)) {
            return $this->set_error($matches[1],true);
        } elseif (preg_match('/^\+OK.+(<.+>)/',$this->greeting,$matches)) {
            $this->timestamp = $matches[1];
        }

        /**
         * fill capability only when connection uses some non-rfc1939
         * authentication type (currently unsupported) or STARTTLS.
         * Command is not part of rfc1939 and we don't have to use it 
         * in simple POP connection.
         */
        if ($this->auth > 3 || $this->tls===2) {
            $this->command_capa();
        }

        // STARTTLS support
        if ($this->tls===2) {
            return $this->command_stls();
        }

        return true;
    }

    /**
     * Reads first response line and checks it for errors
     * @return boolean true = success, false = failure, check error buffer
     */
    function check_response() {
        $line = fgets($this->conn);
        if (preg_match('/^-ERR (.+)/',$line,$matches)) {
            return $this->set_error($matches[1]);
        } elseif (preg_match('/^\+OK/',$line)) {
            $this->response = trim($line);
            return true;
        } else {
            $this->response = trim($line);
            return $this->set_error('Unknown response');
        }
    }

    /**
     * Standard SquirrelMail function copied to class in order to make class 
     * independent from SquirrelMail.
     */
    function check_php_version ($a = '0', $b = '0', $c = '0') {
        return version_compare ( PHP_VERSION, "$a.$b.$c", 'ge' );
    }

    /**
     * Generic login wrapper
     *
     * Connection is not closed on login error (unless POP server drops 
     * connection)
     * @param string $username
     * @param string $password
     * @return boolean
     */
    function login($username,$password) {
        $ret = false;

        // RFC1939 APOP authentication
        if (! $ret && $this->auth & 2) {
            // clean error buffer
            $this->error = '';
            // APOP login
            $ret = $this->command_apop($username,$password);
        }

        // RFC1939 USER authentication
        if (! $ret && $this->auth & 1) {
            // clean error buffer
            $this->error = '';
            // Default to USER/PASS login
            if (! $this->command_user($username)) {
                // error is already in error buffer
                $ret = false;
            } else {
                $ret = $this->command_pass($password);
            }
        }
        return $ret;
    }

    /**
     * Sets error in error buffer and returns boolean false
     * @param string $error Error message
     * @param boolean $close_conn Do we have to close connection
     * @return boolean false
     */
    function set_error($error,$close_conn=false) {
        $this->error = $error;
        if ($close_conn) {
            $this->command_quit();
        }
        return false;
    }

    // POP (rfc 1939) commands

    /**
     * Gets mailbox status
     * array with 'count' and 'size' keys
     * @return mixed array or boolean false
     */
    function command_stat() {
         fwrite($this->conn,"STAT\r\n");
         $response = fgets($this->conn);
         if (preg_match('/^\+OK (\d+) (\d+)/',$response,$matches)) {
            return array('count' => $matches[1],
                         'size'  => $matches[2]);
        } else {
            return $this->set_error('stat command failed');
        }
    }

    /**
     * List mailbox messages
     * @param integer $msg
     * @return mixed array with message ids (keys) and sizes (values) or boolean false
     */
    function command_list($msg='') {
        // add space between command and msg_id
        if(!empty($msg)) $msg = ' ' . $msg;

        fwrite($this->conn,"LIST$msg\r\n");
        
        if($this->check_response()) {
            $ret = array();
            if (!empty($msg)) {
                list($ok,$msg_id,$size) = explode(' ',trim($this->response));
                $ret[$msg_id] = $size;
            } else {
                while($line = fgets($this->conn)) {
                    if (trim($line)=='.') {
                        break;
                    } else {
                        list($msg_id,$size) = explode(' ',trim($line));
                        $ret[$msg_id] = $size;
                    }
                }
            }
            return $ret;
        } else {
            return false;
        }
    }

    /**
     * Gets message text
     * @param integer $msg message id
     * @return mixed rfc822 message (CRLF line endings) or boolean false
     */
    function command_retr($msg) {
        fwrite($this->conn,"RETR $msg\r\n");
        
        if($this->check_response()) {
            $ret = '';
            while($line = fgets($this->conn)) {
                if ($line == ".\r\n") {
                    break;
                } elseif ( $line{0} == '.' ) {
                    $ret .= substr($line,1);
                } else {
                    $ret.= $line;
                }
            }
            return $ret;
        } else {
            return false;
        }
    }

    /**
     * @param integer $msg
     * @return boolean
     */
    function command_dele($msg) {
       fwrite($this->conn,"DELE $msg\r\n");
       return $this->check_response();
    }

    /**
     * POP noop command
     * @return boolean
     */
    function command_noop() {
        fwrite($this->conn,"NOOP\r\n");
        return $this->check_response();
    }

    /**
     * Resets message state
     * @return boolean
     */
    function command_rset() {
        fwrite($this->conn,"RSET\r\n");
        return $this->check_response();
    }

    /**
     * Closes POP connection
     */
    function command_quit() {
        fwrite($this->conn,"QUIT\r\n");
        fclose($this->conn);
        $this->conn = false;
    }

    // Optional RFC1939 commands

    /**
     * Gets message headers and $n of body lines.
     *
     * Command is optional and not required by rfc1939
     * @param integer $msg
     * @param integer $n
     * @return string or boolean false
     */
    function command_top($msg,$n) {
        fwrite($this->conn,"TOP $msg $n\r\n");
        
        if($this->check_response()) {
            $ret = '';
            while($line = fgets($this->conn)) {
                if (trim($line)=='.') {
                    break;
                } else {
                    $ret.= $line;
                }
            }
            return $ret;
        } else {
            return false;
        }
    }

    /**
     * Gets unique message ids
     * Command is optional and not required by rfc1939
     * @param integer $msg message id
     * @return mixed array with message ids (keys) and unique ids (values) 
     * or boolean false
     */
    function command_uidl($msg='') {
        //return $this->set_error('Unsupported command.');
        // add space between command and msg_id
        if(!empty($msg)) $msg = ' ' . $msg;
        fwrite($this->conn,"UIDL$msg\r\n");
        if($this->check_response()) {
            $ids = array();
            if (!empty($msg)) {
                list($ok,$msg_id,$unique_id) = explode(' ',trim($this->response));
                $ids[$msg_id] = "$unique_id";
            } else {
                while($line = fgets($this->conn)) {
                    if (trim($line)=='.') {
                        break;
                    } else {
                        list($msg_id,$unique_id) = explode(' ',trim($line));
                        // make sure that unique_id is a string.
                        $ids[$msg_id] = "$unique_id";
                    }
                }
            }
            return $ids;
        } else {
            return false;
        }
    }

    /**
     * USER authentication (username command)
     * 
     * Command is optional and not required by rfc1939. If command 
     * is successful, pass command must be executed after it.
     * @param string $username
     * @return boolean true = success, false = failure.
     */
    function command_user($username) {
        fwrite($this->conn,"USER $username\r\n");
        return $this->check_response();
    }

    /**
     * USER authentication (password command)
     *
     * Command is optional and not required by rfc1939. Requires
     * successful user command.
     * @param string $password
     * @return boolean true = success, false = failure.
     */
    function command_pass($password) {
        fwrite($this->conn,"PASS $password\r\n");
        return $this->check_response();
    }

    /**
     * APOP authentication
     *
     * Command is optional and not required by rfc1939. APOP support 
     * requires plain text passwords stored on server and some servers 
     * don't support it. Standard qmail pop3d declares apop support 
     * without checking if checkpassword supports it.
     * @param string $username
     * @param string $password
     * @return boolean true = success, false = failure.
     */
    function command_apop($username,$password) {
        if (empty($this->timestamp)) {
            return $this->set_error('APOP is not supported by selected server.');
        }
        $digest = md5($this->timestamp . $password);

        fwrite($this->conn,"APOP $username $digest\r\n");
        return $this->check_response();
    }

    // RFC2449 POP3EXT

    /**
     * Checks pop server capabilities
     * 
     * RFC2449. Fills capabilities array.
     * @return void
     */
    function command_capa() {
        fwrite($this->conn,"CAPA\r\n");
        if ($this->check_response()) {
            // reset array. capabilities depend on authorization state
            $this->capabilities = array();
            while($line = fgets($this->conn)) {
                if (trim($line)=='.') {
                    break;
                } else {
                    $this->capabilities[] = trim($line);
                }
            }
        } else {
            // if capa fails, error buffer contains error message.
            // Clean error buffer,
            // if POP3EXT is not supported, capability array will be empty
            $this->error = '';
        }
    }

    // RFC2595 STARTTLS

    /**
     * RFC 2595 POP STARTTLS support
     * @return boolean
     */
    function command_stls() {
        if (! function_exists('stream_socket_enable_crypto')) {
            return $this->set_error('Used PHP version does not support functions required for POP STLS.',true);
        } elseif (! in_array('STLS',$this->capabilities)) {
            return $this->set_error('Selected POP3 server does not support STLS.',true);
        }
        fwrite($this->conn,"STLS\r\n");
        if (! $this->check_response()) {
            $this->command_quit();
            return false;
        }

        if (@stream_socket_enable_crypto($this->conn,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            // starttls was successful (rfc2595 4. POP3 STARTTLS extension.)
            // get new CAPA response
            $this->command_capa();
        } else {
            /** stream_socket_enable_crypto() call failed. */
            return $this->set_error('Unable to start TLS.',true);
        }
        return true;
    }
}
