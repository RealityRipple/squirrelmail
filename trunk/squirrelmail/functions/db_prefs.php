<?php
   /**
    **  db_prefs.php
    **
    **  This contains functions for manipulating user preferences
    **  stored in a database, accessed though the Pear DB layer.
    **
    **  To use this instead of the regular prefs.php, create a 
    **  database as described below, and replace prefs.php
    **  with this file.
    **
    **  Database:
    **  ---------
    **
    **  The preferences table should have tree columns:
    **     username   char  \  primary
    **     prefkey    char  /  key
    **     prefval    blob
    **
    **    CREATE TABLE userprefs (user CHAR(32) NOT NULL DEFAULT '', 
    **                            prefkey CHAR(64) NOT NULL DEFAULT '', 
    **                            prefval BLOB NOT NULL DEFAULT '', 
    **                            primary key (user,prefkey));
    **
    **  Configuration of databasename, username and password is done
    **  by changing $DSN below.
    **
    **  $Id$
    **/

   if (defined('prefs_php'))
       return;
   define('prefs_php', true);

   require_once('DB.php');

   class dbPrefs {
      var $DSN   = 'mysql://user@host/database';
      var $table = 'userprefs';
      
      var $dbh   = NULL;
      var $error = NULL;

      var $default = Array('chosen_theme'      => '../themes/default_theme.php',
			   'show_html_default' => '0');
      
      function dbPrefs() {
	 $this->open();
      }
      
      function open() {
	 if(isset($this->dbh)) return true;
	 $dbh = DB::connect($this->DSN, true);
	 
	 if(DB::isError($dbh) || DB::isWarning($dbh)) {
	    $this->error = DB::errorMessage($dbh);
	    return false;
	 }
	 
	 $this->dbh = $dbh;
	 return true;
      }
      

      function failQuery($res = NULL) {
	 if($res == NULL) {
	    printf(_("Preference database error (%s). Exiting abnormally"),
		   $this->error);
	 } else {
	    printf(_("Preference database error (%s). Exiting abnormally"),
		   DB::errorMessage($res));
	 }
	 exit;
      }


      function getKey($user, $key) {
	 $this->open();
	 $query = sprintf("SELECT prefval FROM %s ".
			  "WHERE user='%s' AND prefkey='%s'",
			  $this->table, 
			  $this->dbh->quoteString($user),
			  $this->dbh->quoteString($key));

	 $res = $this->dbh->query($query);
	 if(DB::isError($res))
	    $this->failQuery($res);

	 if($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	    return $row['prefval'];
	 } else {
	    if(isset($this->default[$key])) {
	       return $this->default[$key];
	    } else {
	       return '';
	    }
	 }

	 return '';
      }

      function deleteKey($user, $key) {
	 $this->open();
	 $query = sprintf("DELETE FROM %s WHERE user='%s' AND prefkey='%s'",
			  $this->table, 
			  $this->dbh->quoteString($user),
			  $this->dbh->quoteString($key));

	 $res = $this->dbh->simpleQuery($query);
         if(DB::isError($res))
	    $this->failQuery($res);

	 if(substr($key, 0, 9) == 'highlight') {
	    $this->renumberHighlightList($user);
	 }

	 return true;
      }

      function setKey($user, $key, $value) {
	 $this->open();
	 $query = sprintf("REPLACE INTO %s (user,prefkey,prefval) ".
			  "VALUES('%s','%s','%s')",
			  $this->table, 
			  $this->dbh->quoteString($user),
			  $this->dbh->quoteString($key),
			  $this->dbh->quoteString($value));

         $res = $this->dbh->simpleQuery($query);
         if(DB::isError($res)) 
	    $this->failQuery($res);

	 return true;
      }

      
      /**
       ** When a highlight option is deleted the preferences module
       ** must renumber the list.  This should be done somewhere else,
       ** but it is not, so....  
       **/
      function renumberHighlightList($user) {
	 $this->open();
	 $query = sprintf("SELECT * FROM %s WHERE user='%s' ".
			  "AND prefkey LIKE 'highlight%%' ORDER BY prefkey",
			  $this->table, 
			  $this->dbh->quoteString($user));

	 $res = $this->dbh->query($query);
         if(DB::isError($res))
	    $this->failQuery($res);

	 // Store old data in array
	 $rows = Array();
	 while($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) 
	    $rows[] = $row;

	 // Renumber keys of old data
	 $hilinum = 0;
	 for($i = 0; $i < count($rows) ; $i++) {
	    $oldkey = $rows[$i]['prefkey'];
	    $newkey = substr($oldkey, 0, 9) . $hilinum;
	    $hilinum++;

	    if($oldkey != $newkey) {
	       $query = sprintf("UPDATE %s SET prefkey='%s' WHERE user='%s' ".
				"AND prefkey='%s'", 
				$this->table,
				$this->dbh->quoteString($newkey),
				$this->dbh->quoteString($user),
				$this->dbh->quoteString($oldkey));
	       
	       $res = $this->dbh->simpleQuery($query);
	       if(DB::isError($res))
		  $this->failQuery($res);
	    }
	 }

	 return;
      }

   } // end class dbPrefs


   /** returns the value for the pref $string **/
   function getPref($data_dir, $username, $string, $default ) {
      $db = new dbPrefs;
      if(isset($db->error)) {
	 printf(_("Preference database error (%s). Exiting abnormally"),
		$db->error);
	 exit;
      }

      return $db->getKey($username, $string);
   }

   /** Remove the pref $string **/
   function removePref($data_dir, $username, $string) {
      $db = new dbPrefs;
      if(isset($db->error)) $db->failQuery();

      $db->deleteKey($username, $string);
      return;
   }
   
   /** sets the pref, $string, to $set_to **/
   function setPref($data_dir, $username, $string, $set_to) {
      $db = new dbPrefs;
      if(isset($db->error))
	 $db->failQuery();

      $db->setKey($username, $string, $set_to);
      return;
   }

   /** This checks if the prefs are available **/
   function checkForPrefs($data_dir, $username) {
      $db = new dbPrefs;
      if(isset($db->error))
	 $db->failQuery();
   }

   /** Writes the Signature **/
   function setSig($data_dir, $username, $string) {
      $db = new dbPrefs;
      if(isset($db->error)) 
	 $db->failQuery();

      $db->setKey($username, "___signature___", $string);
      return;
   }

   /** Gets the signature **/
   function getSig($data_dir, $username) {
      $db = new dbPrefs;
      if(isset($db->error))
	 $db->failQuery();

      return $db->getKey($username, "___signature___");
   }

?>
