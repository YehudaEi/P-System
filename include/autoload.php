<?php

include_once('../include/config.php');

session_set_cookie_params(0, '/', ".".SERVER_BASE_DOMAIN);
session_name('PSystem');
session_start();

include_once(APP_INCLUDE . DS . 'login.php');
include_once(APP_INCLUDE . DS . 'functions.php');
include_once(APP_INCLUDE . DS . 'browser.php');

