<?php

  /**
   **  addressbook.php
   **
   **  Functions and classes for the addressbook system.
   **
   **/
    
   $addressbook_php = true;

   // Include backends here.
   include("../functions/abook_local_file.php");
   include("../functions/abook_ldap_server.php");

   // Create and initialize an addressbook object. 
   // Returns the created object
   function addressbook_init() {
      global $data_dir, $username, $ldap_server;
      
      // Create a new addressbook object
      $abook = new AddressBook;
      
      // Always add a local backend
      $filename = sprintf("%s%s.abook", $data_dir, $username);
      $r = $abook->add_backend("local_file", Array("filename" => $filename,
						   "create"   => true));
      if(!$r) {
	 print _("Error opening ") ."$filename";
	 exit;
      }
     

      // Load configured LDAP servers
      reset($ldap_server);
      while(list($key,$param) = each($ldap_server))
	 if(is_array($param))
	    $abook->add_backend("ldap_server", $param);

      // Return the initialized object
      return $abook;
   }



  /**
   ** This is the main address book class that connect all the
   ** backends and provide services to the functions above.
   **
   **/
   class AddressBook { 
      var $backends    = array();
      var $numbackends = 0;
      var $error       = "";

      // Constructor function.
      function AddressBook() {
      }

      // Return an array of backends of a given type, 
      // or all backends if no type is specified.
      function get_backend_list($type = "") {
	 $ret = array();
	 for($i = 1 ; $i <= $this->numbackends ; $i++) {
	    if(empty($type) || $type == $this->backends[$i]->btype) {
	       array_push($ret, &$this->backends[$i]);
	    }
	 }
	 return $ret;
      }


      // ========================== Public ========================

      // Add a new backend. $backend is the name of a backend
      // (without the abook_ prefix), and $param is an optional
      // mixed variable that is passed to the backend constructor.
      // See each of the backend classes for valid parameters.
      function add_backend($backend, $param = "") {
	 $backend_name = "abook_".$backend;
	 eval("\$newback = new $backend_name(\$param);");
	 if(!empty($newback->error)) {
	    $this->error = $newback->error;
	    return false;
	 }

	 $this->numbackends++;
       
	 $newback->bnum = $this->numbackends;
	 $this->backends[$this->numbackends] = $newback;
	 return $this->numbackends;
      }


      // Return a list of addresses matching expression in
      // all backends of a given type.
      function search($expression, $btype = "") {
	 $ret = array();

	 $sel = $this->get_backend_list($btype);
	 for($i = 0 ; $i < sizeof($sel) ; $i++) {
	    $backend = &$sel[$i];
	    $backend->error = "";
	    $res = $backend->search($expression);
	    if(is_array($res)) {
	       $ret = array_merge($ret, $res);
	    } else {
	       $this->error = $backend->error;
	       return false;
	    }
	 }

	 return $ret;
      }


      // Return a sorted search
      function s_search($expression, $btype = "") {
	 $ret = $this->search($expression, $btype);

	 // Inline function - Not nice, but still.. 
	 function cmp($a,$b) {   
	    if($a["backend"] > $b["backend"]) 
	       return 1;
	    else if($a["backend"] < $b["backend"]) 
	       return -1;
	 
	    return (strtolower($a["name"]) > strtolower($b["name"])) ? 1 : -1;
	 }

	 usort($ret, 'cmp');
	 return $ret;
      }


      // Lookup an address by alias. Only possible in
      // local backends.
      function lookup($alias) {
	 $ret = array();

	 $sel = $this->get_backend_list("local");
	 for($i = 0 ; $i < sizeof($sel) ; $i++) {
	    $backend = &$sel[$i];
	    $backend->error = "";
	    $res = $backend->lookup($alias);
	    if(is_array($res)) {
	       return $res;
	    } else {
	       $this->error = $backend->error;
	       return false;
	    }
	 }

	 return $ret;
      }


      // Return all addresses
      function list_addr() {
	 $ret = array();

	 $sel = $this->get_backend_list("local");
	 for($i = 0 ; $i < sizeof($sel) ; $i++) {
	    $backend = &$sel[$i];
	    $backend->error = "";
	    $res = $backend->list_addr();
	    if(is_array($res)) {
	       $ret = array_merge($ret, $res);
	    } else {
	       $this->error = $backend->error;
	       return false;
	    }
	 }

	 return $ret;
      }


      // Create a new address from $userdata, in backend $bnum.
      // Return the backend number that the/ address was added
      // to, or false if it failed.
      function add($userdata, $bnum) {

	 // Validate data
	 if(!is_array($userdata)) {
	    $this->error = _("Invalid input data");
	    return false;
	 }
	 if(empty($userdata["fullname"]) &&
	    empty($userdata["lastname"])) {
	    $this->error = _("Name is missing");
	    return false;
	 }
	 if(empty($userdata["email"])) {
	    $this->error = _("E-mail address is missing");
	    return false;
	 }
	 if(empty($userdata["nickname"])) {
	    $userdata["nickname"] = $userdata["email"];
	 }

	 // Check that specified backend accept new entries
	 if(!$this->backends[$bnum]->writeable) {
	    $this->error = _("Addressbook is not writable");
	    return false;
	 }

	 // Add address to backend
	 $res = $this->backends[$bnum]->add($userdata);
	 if($res) {
	    return $bnum;
	 } else {
	    $this->error = $this->backends[$bnum]->error;
	    return false;
	 }

	 return false;  // Not reached
      }

   }


  /**
   ** Generic backend that all other backends extend
   **/
   class addressbook_backend {

      // Variables that all backends must provide.
      var $btype      = "dummy";
      var $bname      = "dummy";
      var $sname      = "Dummy backend";

      // Variables common for all backends, but that 
      // should not be changed by the backends.
      var $bnum       = -1;
      var $error      = "";
      var $writeable  = false;

      function set_error($string) {
	 $this->error = "[" . $this->sname . "] " . $string;
	 return false;
      }


      // ========================== Public ========================

      function search($expression) {
	 $this->set_error("search not implemented");
	 return false;
      }

      function lookup($alias) {
	 $this->set_error("lookup not implemented");
	 return false;
      }

      function list_addr() {
	 $this->set_error("list_addr not implemented");
	 return false;
      }

      function add($userdata) {
	 $this->set_error("add not implemented");
	 return false;
      }

   }

?>
