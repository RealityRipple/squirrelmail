<?php

  /**
   **  printer_friendly_main.php
   **
   **  Copyright (c) 1999-2000 The SquirrelMail development team
   **  Licensed under the GNU GPL. For full terms see the file COPYING.
   **
   **  $Id$
   **/

?><!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">
<html>
  <head>
    <title>Print Email</title>
  </head>
  <frameset rows="50, *" noresize border="0">
    <frame src="printer_friendly_top.php" name="top_frame" scrolling="off">
    <frame src="printer_friendly_bottom.php?passed_ent_id=<?php
  echo $passed_ent_id . '&mailbox=' . urlencode($mailbox) .
       '&passed_id=' . $passed_id;
?>" name="bottom_frame">
  </frameset>
</html>
