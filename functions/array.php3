<?
   function ary_sort($ary,$col, $dir = 1){
      // The globals are used because USORT determines what is passed to comp2
      // These should be $this->col and $this->dir in a class
      // Would beat using globals
      if(!is_array($col)){
         $col = array("$col");
      }
      $GLOBALS["col"] = $col;  // Column or Columns as an array
      $GLOBALS["dir"] = $dir;  // Direction, a positive number for ascending a negative for descending
  
      function comp2($a,$b,$i = 0) {
         global $col;
         global $dir;
         $c = count($col) -1;
         if ($a["$col[$i]"] == $b["$col[$i]"]){
            $r = 0;
            while($i < $c && $r == 0){
               $i++;
               $r = comp2($a,$b,$i);
            }
         } elseif($a["$col[$i]"] < $b["$col[$i]"]){
            $r = -1 * $dir; // Im not sure why you must * dir here, but it wont work just before the return...
         } else {
            $r = 1 * $dir;
         }
         return $r;
      }
  
      usort($ary,comp2);
      return $ary;
   }
?>
