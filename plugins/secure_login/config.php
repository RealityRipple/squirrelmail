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

   global $change_back_to_http_after_login, $remain_in_https_if_logged_in_using_https,
          $allVirtualDomainsUnderOneSSLHost, $sl_securePort, $nonStandardHttpPort,
          $sl_debug, $entryPointDomainPattern, $entryPointPathPattern, 
          $entryPointQueryPattern, $sl_obey_x_forwarded_headers;



   // if you want user sessions to remain in SSL for their entire duration, 
   // set the following to zero:
   //
   $change_back_to_http_after_login = 0;



   // if you want user sessions to remain in SSL only if they originally came
   // in thru SSL (this plugin didn't need to redirect them), set the following 
   // to one:
   //
   $remain_in_https_if_logged_in_using_https = 1;



   // for sites that host all SSL requests for virtual domains 
   // off of a single host URI (commonly used for SSL implementations 
   // using just one certificate for all hosts), where the correct
   // URIs to the SquirrelMail login page look like:
   //
   // https://www.onedomain.com/virtualdomain.com/mail/src/login.php
   //
   // or:
   //
   // https://www.onedomain.com/mail/src/login.php?domain=virtualdomain.com
   //
   // set this value to the pattern that will reproduce the correct
   // SSL URI to the Squirrelmail login page.  Substitutions you can use:
   //
   // ###DOMAIN###  --  The full domain from the original http request,
   //                   such as virtualdomain.com
   // ###PATH###    --  The pah/directory information from the original
   //                   http request, such as /mail or /mail/src/login.php
   // ###QUERY###   --  The query string from the original http request,
   //                   such as ?mynameis=pavel&color=green
   // 
   // The two examples below construct URI patterns just like
   // the URIs given above.
   //
   // $allVirtualDomainsUnderOneSSLHost = 'https://www.onedomain.com/###DOMAIN######PATH###';
   // $allVirtualDomainsUnderOneSSLHost = 'https://www.onedomain.com/mail/src/login.php?domain=###DOMAIN###';
   //
   // NOTE that this setting can also be useful in scenarios where you 
   // need fine-grained control over the encrypted URI, even when the 
   // URI is different for any virtual hosts you may have.  For example:
   //
   // $allVirtualDomainsUnderOneSSLHost = 'https://secret.###DOMAIN###/secret_mail/src/login.php###QUERY###';
   //
   $allVirtualDomainsUnderOneSSLHost = '';



   // the above $allVirtualDomainsUnderOneSSLHost setting assumes that the
   // original plain (unencrypted) http request comes from a URI such as:
   // 
   // http://virutaldomain.com/mail/src/login.php
   //
   // however, if your entry point will also be in a similar format, such as:
   // 
   // http://www.onedomain.com/virtualdomain.com/mail/src/login.php
   //
   // or:
   //
   // http://www.onedomain.com/mail/src/login.php?domain=virtualdomain.com
   //
   // set these values each to a regular expression that will capture:
   //
   //   the domain portion of the URI in the first group (set of parenthesis)
   //   the path portion of the URI in the first group (set of parenthesis)
   //   the query portion of the URI in the first group (set of parenthesis)
   // 
   // otherwise, leave these all set to empty strings.
   //
   // The two examples below pick the domain, path and query string out of 
   // the sample URIs given above.
   //
   // $entryPointDomainPattern = '/[\/]+.+?\/(.+?)(\/|$)/';
   // $entryPointPathPattern   = '/[\/]+.+?\/.+?(\/.*?)(\?|$)/';
   // $entryPointQueryPattern  = '/(\?.*)/';
   //
   // $entryPointDomainPattern = '/domain=(.+?)(&|$)/';
   // $entryPointPathPattern   = '/[\/]+.+?(\/.*?)(\?|$)/';
   // $entryPointQueryPattern  = '/(\&.*)/';
   //
   // NOTE that these settings can also be useful in scenarios where you
   // need better control over the domain parsing of the original entry
   // URI.  This should only be used if the auto-sensing behavior of the
   // plugin will not work.  For example:
   //
   $entryPointDomainPattern = '';
   $entryPointPathPattern   = '';
   $entryPointQueryPattern  = '';



   // by default, https requests are made without explicitly defining the
   // port number.  if you use a non-standard port for serving http requests, 
   // that port will be preserved for the https redirection, which may break
   // your squirrelmail.
   // 
   // if your server listens for https requests on a non-standard port or
   // the above situation applies to you (non-standard http port), you can 
   // specify a non-standard https port number here (or remove it, forcing 
   // the browser use the default port (443)).
   // 
   // if you use this setting, remember to remove the slashes in front of it
   //
   // $sl_securePort = '';
   // $sl_securePort = '888';



   // if you are running regular HTTP requests on a non-standard port
   // (anything besides port 80), please specify that value here
   // if you are using port 80, then you should leave this value empty
   //
   //$nonStandardHttpPort = '80';
   $nonStandardHttpPort = '';



   // If you run SquirrelMail behind a proxy server, where the
   // client domain information is in X_FORWARDED_* headers,
   // enable this setting (set it to 1), otherwise, leave this
   // off (zero) to reduce the chance that someone can try to
   // forge the hostname in their request headers.
   //
   // $sl_obey_x_forwarded_headers = 1;
   $sl_obey_x_forwarded_headers = 0;



   // turn this on for debugging purposes only
   //
   // 1 = show server environment upon entry
   // 2 = show redirect URI and server environment
   //
   $sl_debug = 0;



