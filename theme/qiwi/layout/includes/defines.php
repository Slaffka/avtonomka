<?php
$islogin = false;
if (isloggedin() && !isguestuser()) {
    $islogin = true;
}

$pagecode = '';
if(!empty($_SERVER['REQUEST_URI']) && ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '/login/index.php') ) {
    $pagecode = 'index';
}

$myroles = array();
if($roles = get_user_roles(context_system::instance(), $USER->id)){
    foreach($roles as $role){
        $myroles[$role->shortname] = $role->shortname;
    }
}



?>