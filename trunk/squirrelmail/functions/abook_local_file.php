<?php

  /**
   **  abook_local_file.php
   **
   **  Backend for addressbook as a pipe separated file
   **
   **  An array with the following elements must be passed to
   **  the class constructor (elements marked ? are optional):
   **
   **     filename  => path to addressbook file
   **   ? create    => if true: file is created if it does not exist.
   **   ? umask     => umask set before opening file.
   **
   **  NOTE. This class should not be used directly. Use the
   **        "AddressBook" class instead.
   **/

   class abook_local_file extends addressbook_backend {
     var $btype = "local";
     var $bname = "local_file";

     var $filename   = "";
     var $filehandle = 0;
     var $create     = false;
     var $umask;

     // ========================== Private =======================

     // Constructor
     function abook_local_file($param) {
       $this->sname = _("Personal address book");
       $this->umask = Umask();

       if(is_array($param)) {
	 if(empty($param["filename"]))
	   return $this->set_error("Invalid parameters");
	 if(!is_string($param["filename"]))
	   return $this->set_error($param["filename"] . ": ".
				   _("Not a file name"));

	 $this->filename = $param["filename"];

	 if($param["create"])
	   $this->create = true;
	 if(isset($param["umask"])) 
	   $this->umask = $param["umask"];

	 $this->open(true);
       } else {
	 $this->set_error("Invalid argument to constructor");
       }
     }

     // Open the addressbook file and store the file pointer.
     // Use $file as the file to open, or the class' own 
     // filename property. If $param is empty and file is  
     // open, do nothing.
     function open($new = false) {
       $this->error = "";
       $file   = $this->filename;
       $create = $this->create;

       // Return true is file is open and $new is unset
       if($this->filehandle && !$new)
	 return true;

       // Check that new file exitsts
       if((!(file_exists($file) && is_readable($file))) && !$create)
	 return $this->set_error("$file: " . 
				 _("No such file or directory"));

       // Close old file, if any
       if($this->filehandle) $this->close();
       
       // Open file. First try to open for reading and writing,
       // but fall back to read only.
       umask($this->umask);
       $fh = @fopen($file, "a+");
       if($fh) {
	 $this->filehandle = &$fh;
	 $this->filename   = $file;
	 $this->writeable  = true;
       } else {
	 $fh = @fopen($file, "r");
	 if($fh) {
	   $this->filehandle = &$fh;
	   $this->filename   = $file;
	   $this->writeable  = false;
	 } else {
	   return $this->set_error("$file: "._("Open failed"));
	 }
       }

       return true;
     }

     // Close the file and forget the filehandle
     function close() {
       @fclose($this->filehandle);
       $this->filehandle = 0;
       $this->filename   = "";
       $this->writable   = false;
     }
     
     // ========================== Public ========================
     
     // Search the file
     function search($expr) {

       // To be replaced by advanded search expression parsing
       if(is_array($expr)) return;

       // Make regexp from glob'ed expression 
       $expr = ereg_replace("\?", ".", $expr);
       $expr = ereg_replace("\*", ".*", $expr);

       $res = array();
       if(!$this->open())
	 return false;

       @rewind($this->filehandle);
       
       while ($row = @fgetcsv($this->filehandle, 2048, "|")) {
	 $line = join(" ", $row);
	 if(eregi($expr, $line)) {
	   array_push($res, array("nickname"  => $row[0],
				  "name"      => $row[1] . " " . $row[2],
				  "firstname" => $row[1],
				  "lastname"  => $row[2],
				  "email"     => $row[3],
				  "label"     => $row[4],
				  "backend"   => $this->bnum,
				  "source"    => &$this->sname));
	 }
       }
       
       return $res;
     }
     
     // Lookup alias
     function lookup($alias) {
       if(empty($alias))
	 return array();

       $alias = strtolower($alias);
       
       $this->open();
       @rewind($this->filehandle);
       
       while ($row = @fgetcsv($this->filehandle, 2048, "|")) {
	 if(strtolower($row[0]) == $alias) {
	   return array("nickname"  => $row[0],
			"name"      => $row[1] . " " . $row[2],
			"firstname" => $row[1],
			"lastname"  => $row[2],
			"email"     => $row[3],
			"label"     => $row[4],
			"backend"   => $this->bnum,
			"source"    => &$this->sname);
	 }
       }
       
       return array();
     }

     // List all addresses
     function list_addr() {
       $res = array();
       $this->open();
       @rewind($this->filehandle);
       
       while ($row = @fgetcsv($this->filehandle, 2048, "|")) {
	 array_push($res, array("nickname"  => $row[0],
				"name"      => $row[1] . " " . $row[2],
				"firstname" => $row[1],
				"lastname"  => $row[2],
				"email"     => $row[3],
				"label"     => $row[4],
				"backend"   => $this->bnum,
				"source"    => &$this->sname));
       }
       return $res;
     }

     // Add address
     function add($userdata) {
       if(!$this->writeable) 
	 return $this->set_error(_("Addressbook is read-only"));

       // See if user exist already
       $ret = $this->lookup($userdata["nickname"]);
       if(!empty($ret))
	 return $this->set_error(sprintf(_("User '%s' already exist"), 
					 $ret["nickname"]));

       // Here is the data to write
       $data = sprintf("%s|%s|%s|%s|%s", $userdata["nickname"],
		       $userdata["firstname"], $userdata["lastname"],
		       $userdata["email"], $userdata["label"]);
       // Strip linefeeds
       $data = ereg_replace("[\r\n]", " ", $data);
       // Add linefeed at end
       $data = $data."\n";

       // Reopen file, just to be sure
       $this->open(true);
       if(!$this->writeable) 
	 return $this->set_error(_("Addressbook is read-only"));

       $r = fwrite($this->filehandle, $data);
       if($r > 0)
	 return true;

       $this->set_error(_("Write to addressbook failed"));
       return false;
     }

   }
?>
