<?php

  /**
   **  printer_friendly_top.php
   **
   **  Copyright (c) 1999-2000 The SquirrelMail development team
   **  Licensed under the GNU GPL. For full terms see the file COPYING.
   **
   **  top frame of printer_friendly_main.php
   **  displays some javascript buttons for printing & closing
   **
   **  $Id$
   **/

    require_once('../src/validate.php');
    require_once('../functions/strings.php');
    require_once('../config/config.php');
    require_once('../src/load_prefs.php');

?>
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">
<html>
  <head>
  <script language="javascript">
  <!--
    function printPopup() {
        parent.frames[1].focus();
        parent.frames[1].print();
    }
  -->
  </script>
  </head>
<?php

    if ($theme_css != "")
    {
        printf ('<LINK REL="stylesheet" TYPE="text/css" HREF="%s">', $theme_css);
        echo "\n";
    }


    printf('<body text="%s" bgcolor="%s" link="%s" vlink="%s" alink="%s">',
        $color[8], $color[3], $color[7], $color[7], $color[7]);
?>
    <table width="100%" height="100%" cellpadding="0" cellspacing="0" border="0"><tr><td valign="middle" align="center">
      <b>
      <form>
      <input type="button" value="Print" onClick="printPopup()">
      <input type="button" value="Close Window" onClick="window.parent.close()">
      </form>
      </b>
    </td></tr></table>
  </body>
</html>
