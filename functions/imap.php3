<?
   // *****************************************
   //    Read from the connection until we get either an OK or BAD message.
   // *****************************************
   function imapReadData($connection) {
      $read = fgets($connection, 1024);
      $counter = 0;
      while ((substr($read, strpos($read, " ") + 1, 2) != "OK") && (substr($read, strpos($read, " ") + 1, 3) != "BAD")) {
         $data[$counter] = $read;
         $read = fgets($connection, 1024);
         $counter++;
      }
      return $data;
   }
?>
