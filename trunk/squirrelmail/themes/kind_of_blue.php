<?php
   /** Author:       Jorey Bump
       Date:         October 20, 2001
       Theme Name:   "Kind of Blue"

       This theme generates random colors, featuring a light bluish background with dark text.

   **/

   /** seed the random number generator **/
   sq_mt_randomize();

   for ($i = 0; $i <= 14; $i++) {
	/** background/foreground toggle **/
	if ($i == 0 or $i == 3 or $i == 4 or $i == 5 or $i == 9 or $i == 10 or $i == 12) {
		/** background **/
        	$b = mt_rand(248,255);
        	$g = mt_rand(180,255);
        	$r = mt_rand(178,$g);
	} else {
		/** text **/
		$cmin = 0;
		$cmax = 128;

        	/** generate random color **/
        	$b = mt_rand($cmin,$cmax);
        	$g = mt_rand($cmin,$cmax);
        	$r = mt_rand($cmin,$cmax);
	}

        /** set array element as hex string with hashmark (for HTML output) **/
        $color[$i] = sprintf("#%02X%02X%02X",$r,$g,$b);
   }

   
/** Reference from  http://www.squirrelmail.org/wiki/CreatingThemes

    $color[0]   = '#xxxxxx';  // Title bar at the top of the page header
    $color[1]   = '#xxxxxx';  // Not currently used
    $color[2]   = '#xxxxxx';  // Error messages (usually red)
    $color[3]   = '#xxxxxx';  // Left folder list background color
    $color[4]   = '#xxxxxx';  // Normal background color
    $color[5]   = '#xxxxxx';  // Header of the message index // (From, Date,Subject)
    $color[6]   = '#xxxxxx';  // Normal text on the left folder list
    $color[7]   = '#xxxxxx';  // Links in the right frame
    $color[8]   = '#xxxxxx';  // Normal text (usually black)
    $color[9]   = '#xxxxxx';  // Darker version of #0
    $color[10]  = '#xxxxxx';  // Darker version of #9
    $color[11]  = '#xxxxxx';  // Special folders color (INBOX, Trash, Sent)
    $color[12]  = '#xxxxxx';  // Alternate color for message list // Alters between #4 and this one
    $color[13]  = '#xxxxxx';  // Color for quoted text -- > 1 quote
    $color[14]  = '#xxxxxx';  // Color for quoted text -- >> 2 or more

**/

?>
