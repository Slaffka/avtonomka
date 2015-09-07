<?php

$plugins = array(
    'bootstraporigin' => array('files' => array('bootstrap.js')),
    'tibimenu' => array('files' => array('tibimenu/tibiMenu.js', 'tibimenu/theme/base.css')),
    'grid' => array('files'  => array('grid.js')),
    'print' => array('files'  => array('print.js')),
    'modernizr' => array('files'  => array('modernizr.custom.js')),
    'months_range' => array('files' => array('months_range/months_picker.js', 'months_range/css/months_picker.css')),
    'scroll' => array('files' => array('scroll/scroll.js', 'scroll/scroll.css')),
    'chart.line' => array('files' => array('chart/line.js')),
    'chart.pie' => array('files' => array('chart/pie.js')),
    'chart.column' => array('files' => array('chart/columnchart.js')),
    'chart.balls' => array('files' => array('chart/balls.js')),
    'chart.wave' => array('files' => array('chart/wave.js')),
    'notifications' => array('files' => array('notifications/notifications.css', 'notifications/notifications.js')),
    'app' => array('files' => array('base/app.js')),
    'datepicker' => array('files' => array('datepicker/bootstrap-datepicker.js', 'datepicker/datepicker3.css')),
);

$devicetype = core_useragent::get_user_device_type();
if( $devicetype == core_useragent::DEVICETYPE_TABLET || $devicetype == core_useragent::DEVICETYPE_MOBILE ){
    $plugins['tibimenu']['files'][] = 'tibimenu/theme/shift.css';
}else{
    $plugins['tibimenu']['files'][] = 'tibimenu/theme/default.css';
}