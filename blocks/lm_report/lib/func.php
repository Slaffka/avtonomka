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
    foreach ($arr as $val) {
        $datetime = $time_today + $val * 24 * 60 * 60;
        $date = date("Y-m-d", $datetime);
        $days .= "<div style='width:20%;float:left;padding:20px 0;text-align:center'><a";
        if ($datetime > time()) {
            $days .= " style='color:grey'";
        } else {
            $days .= " href='?date={$datetime}'";
        }
        $days .= ">{$date}</a></div>";
    }
    return $days;
}

function enterEmpty($data, $prop = 'name',$link = false) {
    if ($data == null || $data == false) {
        return "---";
    } else {
        if (!$link)
            return $data->$prop;
        else
            return "<a target='_blank' href='/blocks/manage/?_p=lm_personal&id=".  enterEmpty($data, 'id')."'>{$data->$prop}</a>";
    }
}

// получение данных и упаковка

function getSortData($res) {
        $array = array();
        foreach ($res as $val) {
                $key = null;
                foreach ($array as $k => $v) {
                        if (empty($array) || $val->userid == $v['userid'] && $val->page == $v['module_name']) {
                            $key = $k;
                            break;                
                        }
                }
                if($key !== null) {
                        $array[$key]['detail'][] = array(
                        'section' => $val->subpage,
                        'time' => $val->time
                        );
                        $array[$key]['time_total'] += $val->time;
                } else {
                        $array[] = array(
                                'userid' => $val->userid,
                                'module_name' =>$val->page,
                                'time_total' => $val->time,
                                'detail' => array(
                                        array(
                                                'section' => $val->subpage,
                                                'time' => $val->time
                                        )
                                )
                        );         
                }
        }
        return $array;
}
