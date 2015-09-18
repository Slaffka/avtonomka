<?php

// преобразование времени
function changeTime($time) {
    $h = floor($time / 3600);
    $m = floor(($time - ($h * 3600)) / 60);
    $s = $time - ($h * 3600) - ($m * 60);
    if (mb_strlen($m) == 1) {
        $m = "0" . $m;
    }
    if (mb_strlen($s) == 1) {
        $s = "0" . $s;
    }
    return $h . ":" . $m . ":" . $s;
}

function getDays($time_today) {
    $arr = array(-2, -1, 0, 1, 2);
    $days = '';
    foreach ($arr as $val){
        $datetime = $time_today + $val * 24 * 60 * 60;
        $date = date("Y-m-d", $datetime);
        $days .= "<div style='width:20%;float:left;padding:20px 0;text-align:center'><a";
        if ($datetime > time()){
            $days .= " style='color:grey'";
        } else {
            $days .= " href='?date={$datetime}'";
        }
        $days .= ">{$date}</a></div>";
    }
    return $days;
}

function enterEmpty($data){
    if ($data == null || $data == false){
        return "---";
    } else {
        return $data->name;
    }
}