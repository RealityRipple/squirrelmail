<?php

/**
  * SquirrelMail Secure Login Plugin
  * Copyright (c) 2002 Graham Norbury <gnorbury@bondcar.com>
  * Copyright (c) 2003-2008 Paul Lesniewski <paul@squirrelmail.org>
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * @package plugins
  * @subpackage secure_login
  *
  */



/**
  * Validate that this plugin is configured correctly
  *
  * @return boolean Whether or not there was a
  *                 configuration error for this plugin.
  *
  */
function sl_check_configuration_do()
{

   // just make sure a config file is found
   //
   if (!sl_init())
   {
      do_err('Secure Login plugin could not find any configuration file', FALSE);
      return TRUE;
   }


   return FALSE;

}



/**
  * Initialize this plugin (load config values)
  *
  * @return boolean FALSE if no configuration file could be loaded, TRUE otherwise
  *
  */
function sl_init()
{

   if (!@include_once (SM_PATH . 'plugins/secure_login/config.php'))
      if (!@include_once (SM_PATH . 'plugins/secure_login/config.sample.php'))
         return FALSE;

   return TRUE;

}



/**
  * Takes the current page request and parses it for the host,
  * path and query string.
  *
  * @return array Three element array containing, in this order,
  *               the host string, path string and query string.
  *
  */
function parse_host_path_query()
{
//hmmm not used yet.  concern is that the secure_login_logout thing needs more complex parsing.... so could it be combined with the login parsing too??
}



/**
  * Makes sure login is done in HTTPS protocol
  *
  */
function secure_login_check_do()
{

   global $secure_login_count, $plugin_secure_login_cameInUsingHttps,
          $sl_obey_x_forwarded_headers,
          $allVirtualDomainsUnderOneSSLHost, $sl_securePort, $sl_debug,
          $entryPointDomainPattern, $entryPointPathPattern, $entryPointQueryPattern;
      
//TODO: remove dead code when plugin seems to be working fine w/out it for a while (NOTE that this includes some live code that still checks the value of various login counters on the login page and NOTE that it does NOT include counter stuff used on the redirect.php page request)
/*
   sqGetGlobalVar('secure_login_count', $secure_login_count, SQ_SESSION);
   if (!isset($secure_login_count))
   {
      $secure_login_count = 0;
      $secure_logoff_count = 0;
      sqsession_register($secure_login_count, 'secure_login_count');
      sqsession_register($secure_logoff_count, 'secure_logoff_count');
      sqsession_unregister('plugin_secure_login_cameInUnencrypted');
   }
*/
      $secure_logoff_count = 0;
      sqsession_register($secure_logoff_count, 'secure_logoff_count');
      sqsession_unregister('plugin_secure_login_cameInUnencrypted');


   sl_init();


   // debug functionality
   // 
   if ($sl_debug == 1)
   {  
      echo "<hr />";
      sm_print_r($_SERVER);
      echo "<hr />";
      exit;
   }


      
   // grab port the user came in on
   //
   if (!sqGetGlobalVar('SERVER_PORT', $serverPort, SQ_SERVER))
      $serverPort = 0;



   // figure out what port we should be comparing
   //
   if (isset($sl_securePort))
      $targetHttpsPort = $sl_securePort;
   else
      $targetHttpsPort = '443';



   if (!$sl_obey_x_forwarded_headers
    || !sqGetGlobalVar('HTTP_X_FORWARDED_HOST', $requestHost, SQ_SERVER))
      sqGetGlobalVar('HTTP_HOST', $requestHost, SQ_SERVER);
   sqGetGlobalVar('REQUEST_URI', $pathURI, SQ_SERVER);
   sqGetGlobalVar('PHP_SELF', $php_self, SQ_SERVER);
   sqGetGlobalVar('QUERY_STRING', $query_string, SQ_SERVER);


// Nah, let's just use PHP_SELF and QUERY_STRING...
// apparently some PHP versions have a bug regarding
// REQUEST_URI
//   $paramsURI = '';
//   if (preg_match('/(.*)\?(.*)/', $pathURI, $matches))
//   {
//      if (isset($matches[1])) $pathURI = $matches[1];
//      if (isset($matches[2])) $paramsURI = $matches[2];
//   }
   $pathURI = $php_self;
   $paramsURI = trim($query_string, '?&');


         
   // Redirect browser to use https:// if the initial request was insecure
   //
// old way:
//   if ( ! isset($_SERVER['HTTPS']) &&
//        $secure_login_count == 0)
   if ( $serverPort != $targetHttpsPort &&
        $secure_login_count == 0)
   {  

//TODO: remove dead code when plugin seems to be working fine w/out it for a while (NOTE that this includes some live code that still checks the value of various login counters on the login page and NOTE that it does NOT include counter stuff used on the redirect.php page request)
      //$secure_login_count++;
      //sqsession_register($secure_login_count, 'secure_login_count');
         

      // parse out parts of original request (domain, path, query string)
      //
      if (isset($sl_securePort))
      {
         $sl_securePort = ':' . $sl_securePort;
         
         if (strpos($requestHost, ':') !== FALSE)
            list($host, $ignore) = explode(':', $requestHost);
         else 
            $host = $requestHost;

         $newRequestHost = $host . $sl_securePort;
      }
      else
         $newRequestHost = $requestHost;
       
         
      // if user wants to override original request URI 
      // parsing, do that now
      //
      $master_search_string = 'http://' . $requestHost . (strpos($php_self, '/') === 0 ? '' : '/') . $php_self . (empty($query_string) ? '' : (strpos($query_string, '?') === 0 ? '' : '?') . $query_string);
      if ($entryPointDomainPattern)
      {
         preg_match($entryPointDomainPattern, $master_search_string, $matches);
         $newRequestHost = $matches[1];
      }
      if ($entryPointPathPattern)
      {
         preg_match($entryPointPathPattern, $master_search_string, $matches);
         $pathURI = $matches[1];
      }
      if ($entryPointQueryPattern)
      {
         preg_match($entryPointQueryPattern, $master_search_string, $matches);
         $paramsURI = $matches[1];
         $paramsURI = trim($paramsURI, '?&');
      }


      // build redirect target location
      //
      $location = 'https://' 
                . $newRequestHost . $pathURI . '?secure_login=yes';
      
      if (!empty($paramsURI))
         $location = $location . '&' . $paramsURI;


      // if the URI pattern is given by the config file, 
      // forget all the above...
      //
      if ($allVirtualDomainsUnderOneSSLHost)
      {
         $location = str_replace(array('###DOMAIN###', '###PATH###', '###QUERY###'), 
                                 array($requestHost, $pathURI, 
                                       (strpos($paramsURI, '?') === 0 ? '' : '?') . $paramsURI), 
                                 $allVirtualDomainsUnderOneSSLHost); 
         if (strpos($location, '?') === FALSE)
            $location .= '?';
         else
            $location .= '&';
         $location .= 'secure_login=yes';
      }

      
      // debug functionality
      // 
      if ($sl_debug == 2)
      {  
         echo "<hr />REDIRECT LOCATION: $location<hr />";
         sm_print_r($_SERVER);
         echo "<hr />";
         exit;
      }

      
      header("Location: $location");
      exit();

   }
// old way
//   else if (isset($_SERVER['HTTPS'])) 
   else if ( $serverPort == $targetHttpsPort )
   {

      if (sqGetGlobalVar('secure_login', $ignore, SQ_GET))
      {
         $plugin_secure_login_cameInUnencrypted = 'yes';
         sqsession_register($plugin_secure_login_cameInUnencrypted, 
                            'plugin_secure_login_cameInUnencrypted');
      }
      else if (!empty($allVirtualDomainsUnderOneSSLHost) && !$secure_login_count)
      {

//TODO: remove dead code when plugin seems to be working fine w/out it for a while (NOTE that this includes some live code that still checks the value of various login counters on the login page and NOTE that it does NOT include counter stuff used on the redirect.php page request)
         //$secure_login_count++;
         //sqsession_register($secure_login_count, 'secure_login_count');

         // if coming from logout screen or error page, we've already
         // adjusted the URI as needed - don't try to do it twice:
         //
         $test_pattern = str_replace(array('###DOMAIN###', '###PATH###', '###QUERY###'),
                                     '.*?',
                                     preg_quote($allVirtualDomainsUnderOneSSLHost, '/'));
         if (preg_match('/' . $test_pattern . '/',
                        'https://' . $requestHost . $pathURI
                      . (empty($paramsURI) ? '' : (strpos($paramsURI, '?') === 0 ? '' : '?')
                      . $paramsURI)))
         {
            return;
         }
         else
         {
            $location = str_replace(array('###DOMAIN###', '###PATH###', '###QUERY###'), 
                                    array($requestHost, $pathURI, 
                                          (strpos($paramsURI, '?') === 0 ? '' : '?') . $paramsURI), 
                                    $allVirtualDomainsUnderOneSSLHost); 
         }


         // debug functionality
         // 
         if ($sl_debug == 2)
         {  
echo 'PREG MATCH:<br />/' . $test_pattern . '/<br />https://' . $requestHost . $pathURI . (empty($paramsURI) ? '' : (strpos($paramsURI, '?') === 0 ? '' : '?') . $paramsURI);
            echo "<hr />REDIRECT LOCATION: $location<hr />";
            sm_print_r($_SERVER);
            echo "<hr />";
            exit;
         }

      
         header("Location: $location");
         exit;

      }
   }
}



/**
  * Redirects back to HTTP protocol after login if necessary
  *
  */
function secure_login_logout_do()
{

   global $change_back_to_http_after_login, $sl_securePort,
          $remain_in_https_if_logged_in_using_https,
          $plugin_secure_login_cameInUnencrypted, $secure_login_count;


   sl_init();


//TODO: remove dead code when plugin seems to be working fine w/out it for a while (NOTE that this includes some live code that still checks the value of various login counters on the login page and NOTE that it does NOT include counter stuff used on the redirect.php page request)
   //$secure_login_count = 0;
   //sqsession_register($secure_login_count, 'secure_login_count');


   if (!$change_back_to_http_after_login)
      return;


   if (!sqsession_is_registered('plugin_secure_login_cameInUnencrypted')
       && $remain_in_https_if_logged_in_using_https) 
      return;


   global $secure_logoff_count;
   sqGetGlobalVar('secure_logoff_count', $secure_logoff_count, SQ_SESSION);
   if (!isset($secure_logoff_count))
   {
      $secure_logoff_count = 0;
      sqsession_register($secure_logoff_count, 'secure_logoff_count');
   }


   // need to fix the sq_base_url session varible, since
   // it is put in the session in redirect.php with
   // the https protocol before we get here
   //
   global $sq_base_url, $nonStandardHttpPort;
   sqGetGlobalVar('sq_base_url', $sq_base_url, SQ_SESSION);
   $sq_base_url = str_replace('https://', 'http://', $sq_base_url);
   if (isset($nonStandardHttpPort) && !empty($nonStandardHttpPort))
      $sq_base_url = preg_replace('/:\d\//', ":$nonStandardHttpPort/", $sq_base_url);
   else
      $sq_base_url = preg_replace('/:\d\//', '', $sq_base_url);
   sqsession_register($sq_base_url, 'sq_base_url');


   // grab port the user came in on
   //
   if (!sqGetGlobalVar('SERVER_PORT', $serverPort, SQ_SERVER))
      $serverPort = 0;



   // figure out what port we should be comparing
   //
   if (isset($sl_securePort))
      $targetHttpsPort = $sl_securePort;
   else
      $targetHttpsPort = '443';



   if ( $serverPort == $targetHttpsPort && $secure_logoff_count == 0)
// old way...
//   if ( ! isset($_SERVER['HTTP']) && $secure_logoff_count == 0)
   { 

      $secure_logoff_count++; 
      sqsession_register($secure_logoff_count, 'secure_logoff_count'); 
      if (!sqGetGlobalVar('HTTP_X_FORWARDED_HOST', $requestHost, SQ_SERVER) || empty($requestHost))
         sqGetGlobalVar('HTTP_HOST', $requestHost, SQ_SERVER);
      sqGetGlobalVar('PHP_SELF', $phpSelf, SQ_SERVER);
      sqGetGlobalVar('QUERY_STRING', $query_string, SQ_SERVER);
      $location = 'http://' . $requestHost . $phpSelf . (empty($query_string) ? '' : (strpos($query_string, '?') === 0 ? '' : '?') . $query_string); 

      displayHtmlHeader('',
         "\n<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"0;URL=$location\">\n");

      // note that this causes a pop-up in some browsers (such as IE)
      // that notifies you you are leaving a secure site
      //
      //header("Location: $location");

      exit; 

   }

}


