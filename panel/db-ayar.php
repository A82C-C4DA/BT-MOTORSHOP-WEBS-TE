<?php

$site = 'https://btmotorshop.com/';

//upload dosya yolu
$targetFolder = '/upload/';

try {
     $db = new PDO("mysql:host=localhost;dbname=btmotors_batuece;charset=utf8", "btmotors_batu","Batuece123.");
} catch ( PDOException $e ){
     print $e->getMessage();
}



// Time Zone
date_default_timezone_set('Europe/Istanbul');

// $ssl        = 'on or off';
$ssl                = 'on';

// $www        = 'on or off';
$www                = 'off';

// sss Redirect
$http_https = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
if($ssl == 'on' AND $http_https == 'http://'){
     if(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off"){
         header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
         exit();
     }
}else if($ssl == 'off' AND $http_https == 'https://'){
    header('Location: http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    exit();
}

// www Redirect
if($www == 'on' AND substr($_SERVER['HTTP_HOST'], 0, 4) != 'www.'){
    header('Location: '.$http_https.'www.'.$_SERVER['HTTP_HOST']);
    exit();
}else if($www == 'off' AND substr($_SERVER['HTTP_HOST'], 0, 4) == 'www.'){
    header('Location: '.$http_https.str_replace('www.','',$_SERVER['SERVER_NAME']));
    exit();
}
?>