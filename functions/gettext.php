<?PHP

   /* Alternate to the system's built-in gettext.
    * relies on .po files (can't read .mo easily).
    * Uses the session for caching (speed increase)
    */
     
   if (defined('gettext_php'))
      return;
   define('gettext_php', true);
   
   global $gettext_php_domain, $gettext_php_dir, $gettext_php_loaded,
      $gettext_php_translateStrings;
   
   if (! isset($gettext_php_loaded)) {
      $gettext_php_loaded = false;
      session_register('gettext_php_loaded');
   }
   
   function gettext_php_load_strings() {
      global $sm_language, $gettext_php_translateStrings,
         $gettext_php_domain, $gettext_php_dir, $gettext_php_loaded;
      
      // $sm_language gives 'en' for English, 'de' for German, etc.
      // I didn't wanna use getenv or similar.
      
      $gettext_php_translateStrings = array();
      session_register('gettext_php_translateStrings');
      
      $filename = $gettext_php_dir;
      if (substr($filename, -1) != '/')
         $filename .= '/';
      $filename .= $sm_language . '/LC_MESSAGES/' . $gettext_php_domain . '.po';
      
      $file = fopen($filename, 'r');
      if ($file === false)
         return;
	  
      $key = '';
      $SkipRead = false;
      while (! feof($file)) {
         if (! $SkipRead)
            $line = trim(fgets($file, 4096));
	 else
	    $SkipRead = false;
	     
         if (ereg('^msgid "(.*)"$', $line, $match)) {
	    if ($match[1] == '') {
	       // Potential multi-line
	       $key = '';
               $line = trim(fgets($file, 4096));
	       while (ereg('^[ ]*"(.*)"[ ]*$', $line, $match)) {
		  $key .= $match[1];
		  $line = trim(fgets($file, 4096));
	       }
	    } else {
	       $key = $match[1];
	    }
	 } elseif (ereg('^msgstr "(.*)"$', $line, $match)) {
	    if ($match[1] == '') {
	       // Potential multi-line
	       $gettext_php_translateStrings[$key] = '';
	       $line = trim(fgets($file, 4096));
	       while (ereg('^[ ]*"(.*)"[ ]*$', $line, $match)) {
		  $gettext_php_translateStrings[$key] .= $match[1];
		  $line = trim(fgets($file, 4096));
	       }
	    } else {
	       $gettext_php_translateStrings[$key] = $match[1];
	    }
	    $key = '';
	 }
      }
      fclose($file);
      
      $gettext_php_loaded = true;
   }
   
   function _($str) {
      global $gettext_php_loaded;
	 
      if (! $gettext_php_loaded)
         gettext_php_load_strings();
      
      if (isset($gettext_php_translateStrings[$str]))
         return $gettext_php_translateStrings[$str];
      
      return $str;
   }
   
   function bindtextdomain($name, $dir) {
      global $gettext_php_domain;
      
      $gettext_php_domain = $name;
      $gettext_php_dir = $dir;
      $gettext_php_loaded = false;
      
      return $dir;
   }
   
   function textdomain($name = false) {
      global $gettext_php_domain;
      
      if ($name != false)
      {
         $gettext_php_domain = $name;
	 $gettext_php_loaded = false;
      }
      return $gettext_php_domain;
   }
   
