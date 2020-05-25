<?php

define('HTTPS', ( empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == 'off' ? false : true ));

define('SERVER_BASE_DOMAIN', "p.yehudae.net");
define('SERVER_BASE_URL', (HTTPS ? "https" : "http") . "://" . SERVER_BASE_DOMAIN);

define('FAVICON', "https://yehudae.net/favicon.ico"); # or base64 (data:image/png;base64,)

define('CUSTOM_USER_AGENT', null);
define('CUSTOM_REFERRER', null);

define('USERS', array(
    'admin' => password_hash('admin@123', PASSWORD_DEFAULT),
    'user' => password_hash('12345', PASSWORD_DEFAULT),
    'john' => password_hash('johndoe', PASSWORD_DEFAULT),
));

define('ADMINS' , array(
    'admin' => 1, // admin can view logs
    'user' => 0, // user can't view logs
));

define('LOGGING', 1);

define('DEBUG_MODE', 0);

if(DEBUG_MODE){
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
else{
    error_reporting(0);
    ini_set('display_errors', 0);
}

set_time_limit(0);

define('DS', DIRECTORY_SEPARATOR);

// Credit: https://github.com/NoamDev
define('URL_REGEX', '/(?:https?:)?(?:\/\/|\\\\\\/\\\\\\/)(?:(?:(?:[a-z0-9\-]+\.)+[a-z]{2,})|(?:(?:\d{1,3}\.){3}\d{1,3}))(?=["\'\s\/\:])/i');

define('SESSION_NAME', 'ProxySystem');

define('APP_ROOT', dirname(dirname(__FILE__)). DS ."public". DS);
define('APP_TMP', APP_ROOT . ".." . DS . "tmp");
define('APP_INCLUDE', APP_ROOT . ".." . DS . "include");

