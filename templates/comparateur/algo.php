<?php
 
 echo "<pre>";
$sum = 12 ; //SUM
$array = array(6,1,3,11,2,5,12);
$list = array();
# Extract All Unique Conbinations
extractList($array, $list);
#Filter By SUM = $sum     $list =
array_filter($list,function($var) use ($sum) { return(array_sum($var) == $sum);});
#Return Output
var_dump($list);

function extractList($array, &$list, $temp = array()) {
    if(count($temp) > 0 && ! in_array($temp, $list))
        $list[] = $temp;
    for($i = 0; $i < sizeof($array); $i ++) {
        $copy = $array;
        $elem = array_splice($copy, $i, 1);
        if (sizeof($copy) > 0) {
            $add = array_merge($temp, array($elem[0]));
            sort($add);
            extractList($copy, $list, $add);
        } else {
            $add = array_merge($temp, array($elem[0]));
            sort($add);
            if (! in_array($temp, $list)) {
                $list[] = $add;
            }
        }
    }
}