<?php

if( mail("lobovmaxim@yandex.ru", "My Subject", "Hello") ){
	echo 'OK';
}else{
	echo ' :(';
}