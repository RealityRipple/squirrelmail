<?
   //*************************************************************************
   // Takes a date and parses it into usable format
   //
   // Tue, 29 Jun 1999 09:52:11 -0500 (EDT)
   //
   // $dateParts[0] == <day of week>   Mon, Tue, Wed
   // $dateParts[1] == <day of month>  23
   // $dateParts[2] == <month>         Jan, Feb, Mar
   // $dateParts[3] == <year>          1999
   // $dateParts[4] == <time>          18:54:23 (HH:MM:SS)
   // $dateParts[5] == <from GMT>      +0100
   // $dateParts[6] == <zone>          (EDT)
   //
   //*************************************************************************

   function getHour($hour) {
      $time = explode(":", $hour);
      return $time[0];
   }

   function getMinute($min) {
      $time = explode(":", $min);
      return $time[1];
   }

   function getSecond($sec) {
      $time = explode(":", $sec);
      return $time[2];
   }

   function getMonthNum($month) {
      if (eregi("jan|january", $month, $tmp))
         $date = "01";
      else if (eregi("feb|february|febuary", $month, $tmp))
         $date = "02";
      else if (eregi("mar|march", $month, $tmp))
         $date = "03";
      else if (eregi("apr|april", $month, $tmp))
         $date = "04";
      else if (eregi("may", $month, $tmp))
         $date = "05";
      else if (eregi("jun|june", $month, $tmp))
         $date = "06";
      else if (eregi("jul|july", $month, $tmp))
         $date = "07";
      else if (eregi("aug|august", $month, $tmp))
         $date = "08";
      else if (eregi("sep|sept|september", $month, $tmp))
         $date = "09";
      else if (eregi("oct|october", $month, $tmp))
         $date = "10";
      else if (eregi("nov|november", $month, $tmp))
         $date = "11";
      else if (eregi("dec|december", $month, $tmp))
         $date = "12";

      return $date;
   }

   function getDayOfWeek($day) {
      $date = "{WEEKDAY}";

      if (eregi("(mon|monday)", $day, $tmp))
         $date = "Mon";
      else if (eregi("(tue|tuesday)", $day, $tmp))
         $date = "Tue";
      else if (eregi("(wed|wednesday)", $day, $tmp))
         $date = "Wed";
      else if (eregi("(thurs|thu|thursday)", $day, $tmp))
         $date = "Thu";
      else if (eregi("(fri|friday)", $day, $tmp))
         $date = "Fri";
      else if (eregi("(sat|saturday)", $day, $tmp))
         $date = "Sat";
      else if (eregi("(sun|sunday)", $day, $tmp))
         $date = "Sun";

      return $date;
   }

   function getDayOfMonth($day) {
      return ereg_replace("^0", "", $day); /* remove a preceeding 0 */
   }

   function getMonth($month) {
      $date = "{MONTH}";
      if (eregi("jan|january", $month, $tmp))
         $date = "Jan";
      else if (eregi("feb|february|febuary", $month, $tmp))
         $date = "Feb";
      else if (eregi("mar|march", $month, $tmp))
         $date = "Mar";
      else if (eregi("apr|april", $month, $tmp))
         $date = "Apr";
      else if (eregi("may", $month, $tmp))
         $date = "May";
      else if (eregi("jun|june", $month, $tmp))
         $date = "Jun";
      else if (eregi("jul|july", $month, $tmp))
         $date = "Jul";
      else if (eregi("aug|august", $month, $tmp))
         $date = "Aug";
      else if (eregi("sep|sept|september", $month, $tmp))
         $date = "Sep";
      else if (eregi("oct|october", $month, $tmp))
         $date = "Oct";
      else if (eregi("nov|november", $month, $tmp))
         $date = "Nov";
      else if (eregi("dec|december", $month, $tmp))
         $date = "Dec";

      return $date;
   }

   function getYear($year) {
      return $year;
   }

   function getDateString($dateParts) {
      /* if the first part is a day */
      if (eregi("mon|tue|wed|thu|fri|sat|sun", $dateParts[0], $tmp)) {
         $dateParts[0] = getDayOfWeek($dateParts[0]);
         $dateParts[1] = getDayOfMonth($dateParts[1]);
         $dateParts[2] = getMonth($dateParts[2]);
         $dateParts[3] = getYear($dateParts[3]);
         return "$dateParts[2] $dateParts[1], $dateParts[3]";
      }
      $dateParts[0] = getDayOfMonth($dateParts[0]);
      $dateParts[1] = getMonth($dateParts[1]);
      $dateParts[2] = getYear($dateParts[2]);
      return "$dateParts[1] $dateParts[0], $dateParts[2]";
   }

   function getTimeStamp($dateParts) {
      $d[0] = getHour($dateParts[4]);
      $d[1] = getMinute($dateParts[4]);
      $d[2] = getSecond($dateParts[4]);
      $d[3] = getMonthNum($dateParts[2]);
      $d[4] = getDayOfMonth($dateParts[1]);
      $d[5] = getYear($dateParts[3]);
      return mktime($d[0], $d[1], $d[2], $d[3], $d[4], $d[5]);
   }
?>
