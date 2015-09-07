<?php
$islogin = false;
if (isloggedin() && !isguestuser()) {
    $islogin = true;
}

$pagecode = '';
if(!empty($_SERVER['REQUEST_URI']) && ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '/login/index.php') ) {
    $pagecode = 'index';
}