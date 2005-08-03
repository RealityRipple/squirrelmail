<?php
/**
 * error.class.php
 *
 * Copyright (c) 2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains the custom error handler for SquirrelMail.
 *
 * @version $Id$
 * @package squirrelmail
 */

define('SQM_NOTICE',0);
define('SQM_WARNING',1);
define('SQM_ERROR',2);
define('SQM_STRICT',3);
// php5 E_STRICT constant (compatibility with php4)
if (! defined('E_STRICT')) define('E_STRICT',2048);
// Set docref_root (fixes URLs that link to php manual)
if (ini_get('docref_root')=='') ini_set('docref_root','http://www.php.net/');

/**
 * Error Handler class
 *
 * This class contains a custom error handler in order to display
 * user notices/warnings/errors and php notices and warnings in a template
 *
 * @author  Marc Groot Koerkamp
 * @package squirrelmail
 */
class ErrorHandler {

    /**
     * Constructor
     * @param  object $oTemplate Template object
     * @param  string $sTemplateFile Template containing the error template
     * @since 1.5.1
     */
    function ErrorHandler(&$oTemplate, $sTemplateFile) {
        $this->TemplateName = $sTemplateFile;
        $this->Template =& $oTemplate;
        $this->aErrors = array();
    }

    /**
     * Sets the error template
     * @since 1.5.1
     */
    function SetTemplateFile($sTemplateFile) {
        $this->TemplateFile = $sTemplateFile;
    }

    /**
     * Custom Error handler (set with set_error_handler() )
     * @private
     * @since 1.5.1
     */
    function SquirrelMailErrorhandler($iErrNo, $sErrStr, $sErrFile, $iErrLine, $aContext) {
        $aError = array(
                        'type'     => SQM_NOTICE,// Error type, notice, warning or fatal error;
                        'category' => NULL,      // SquirrelMail error category;
                        'message'  => NULL,      // Error display message;
                        'extra'    => NULL,      // Key value based array with extra error info;
                        'link'     => NULL,      // Link to help location;
                        'tip'      => NULL       // User tip.
                  );
        $iType = NULL;
        $aErrorCategory = array();

        /**
         * Get current error reporting level.
         *
         * PHP 4.1.2 does not return current error reporting level in ini_get (php 5.1b3 and 
         * 4.3.10 does). Retrieve current error reporting level while setting error reporting 
         * to ini value and reset it to retrieved value.
         */
        $iCurErrLevel = error_reporting(ini_get('error_reporting'));
        error_reporting($iCurErrLevel);

        /**
         * Check error_reporting value before logging error.
         * Don't log errors that are disabled by @ (error_reporting = 0). Some SquirrelMail scripts
         * (sq_mb_list_encodings(), ldap function calls in functions/abook_ldap_server.php)
         * handle errors themselves and @ is used to disable generic php error messages.
         */
        if ((bool) $iCurErrLevel) {
            /*
             * The following errors cannot be handled by a user defined error handler:
             * E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING
             */
            switch ($iErrNo) {
            case E_STRICT:
                $iType = (is_null($iType)) ? SQM_STRICT : $iType;
            case E_NOTICE:
                $iType = (is_null($iType)) ? SQM_NOTICE : $iType;
            case E_WARNING:
                $iType = (is_null($iType)) ? SQM_WARNING : $iType;
                $aErrorCategory[] = 'PHP';
                $aError['message'] = $sErrStr;
                $aError['extra'] = array(
                                         'FILE' => $sErrFile,
                                         'LINE' => $iErrLine) ;;
                // what todo with $aContext?
                break;
            case E_USER_ERROR:
                $iType = (is_null($iType)) ? SQM_ERROR : $iType;
            case E_USER_NOTICE:
                $iType = (is_null($iType)) ? SQM_NOTICE : $iType;
            case E_USER_WARNING:
                $iType = (is_null($iType)) ? SQM_WARNING : $iType;
                if ($sErrFile == __FILE__) { // Error is triggered in this file and probably by sqm_trigger_error
                    $aErrorTemp = @unserialize($sErrStr);
                    if (!is_array($aErrorTemp)) {
                        $aError['message'] = $sErrStr;
                        $aErrorCategory[] = 'UNDEFINED';
                    } else {
                        $aError = array_merge($aError,$aErrorTemp);
                        // special error handling below
                        if ($aError['category'] & SQM_ERROR_IMAP) {
                            $aErrorCategory[] = 'IMAP';
                            // imap related error handling inside
                        }
                        if ($aError['category'] & SQM_ERROR_FS) {
                            $aErrorCategory[] = 'FILESYSTEM';
                            // filesystem related error handling inside
                        }
                        if ($aError['category'] & SQM_ERROR_SMTP) {
                            $aErrorCategory[] = 'SMTP';
                            // smtp related error handling inside
                        }
                        if ($aError['category'] & SQM_ERROR_LDAP) {
                            $aErrorCategory[] = 'LDAP';
                            // ldap related error handling inside
                        }
                        if ($aError['category'] & SQM_ERROR_DB) {
                            $aErrorCategory[] = 'DATABASE';
                            // db related error handling inside
                        }
                        if ($aError['category'] & SQM_ERROR_PLUGIN) {
                            $aErrorCategory[] = 'PLUGIN';
                            do_hook_function('error_handler_plugin',$aError);
                            // plugin related error handling inside
                        }
                        //if ($aError['category'] & SQM_ERROR_X) {
                        //     $aErrorCategory[] = 'X';
                        // place holder for a new category
                        //}
                    }
                    unset($aErrorTemp);
                } else {
                    $aError['message'] = $sErrStr;
                    $aErrorCategory[] = 'SQM_NOTICE';
                }
                break;
            default: break;
            }

            $aErrorTpl = array(
                'type'      => $iType,
                'category'  => $aErrorCategory,
                'message'   => $aError['message'],
                'link'      => $aError['link'],
                'tip'       => $aError['tip'],
                'extra'     => $aError['extra']);
            // Add the notice/warning/error to the existing list of notices/warnings
            $this->aErrors[] = $aErrorTpl;
            $this->Template->assign('aErrors',$this->aErrors);
        }

        // Show the error immediate in case of fatal errors
        if ($iType == SQM_ERROR) {
            $this->DisplayErrors();
            exit(_("Terminating SquirrelMail due to a fatal error"));
        }
    }

    /**
     * Display the error array in the error template
     * @return void
     * @since 1.5.1
     */
    function DisplayErrors() {
        if (count($this->aErrors)) {
            $this->Template->display($this->TemplateName);
        }
    }
}

/**
 * Custom Error handler for PHP version < 4.3.0 (set with set_error_handler() )
 * @author  Marc Groot Koerkamp
 * @since 1.5.1
 */
function SquirrelMailErrorhandler($iErrNo, $sErrStr, $sErrFile, $iErrLine, $aContext) {
    global $oTemplate;
    static $oErrorHandler;
    if (!isset($oErrorHandler)) {
        $oErrorHandler = new ErrorHandler($oTemplate,'error_message.tpl');
    }
    $oErrorHandler->SquirrelMailErrorhandler($iErrNo, $sErrStr, $sErrFile, $iErrLine, $aContext);
}

/**
 * Triggers an imap error. Utility function for sqm_trigger_error()
 * @param  integer $iErrNo error number defined in errors.php
 * @param  string $sRequest imap request string
 * @param  string $sResponse tagged imap response
 * @param  string $sMessage tagged imap response message
 * @param  array  $aExtra optional associative array with extra error info
 * @return void
 * @author  Marc Groot Koerkamp
 * @since 1.5.1
 */
function sqm_trigger_imap_error($iErrNo,$sRequest,$sResponse, $sMessage, $aExtra=array()) {
    $aError = array(
                    'REQUEST' => $sRequest,
                    'RESPONSE' => $sResponse,
                    'MESSAGE' => $sMessage);
    $aError = array_merge($aExtra,$aError);
    sqm_trigger_error($iErrNo,$aError);
}

/**
 * Trigger an error.
 * @param  integer $iErrNo error number defined in errors.php
 * @param  array   $aExtra optional associative array with extra error info
 * @return void
 * @author  Marc Groot Koerkamp
 * @since 1.5.1
 */
function sqm_trigger_error($iErrNo,$aExtra=array()) {
    // Include the error definition file.
    include_once(SM_PATH.'include/errors.php');
    $iPhpErr = E_USER_NOTICE;
    if (is_array($aErrors) && isset($aErrors[$iErrNo]['level'])) {
        if (is_array($aExtra) && count($aExtra)) {
            $aErrors[$iErrNo]['extra'] = $aExtra;
        }
        // because trigger_error can only handle a string argument for the error description
        // we serialize the result.
        $sErrString = serialize($aErrors[$iErrNo]);
        $iPhpErr = $aErrors[$iErrNo]['level'];
    } else {
        $sErrString = "Error number <$iErrNo> does not exist, fix the code or update the errors.php file";
        $iPhpErr = E_USER_ERROR;
    }
    trigger_error($sErrString, $iPhpErr);
}
?>