<?php

ini_set('memory_limit', '2048M');
set_time_limit(0);

session_start();

ini_set('error_reporting', 'E_ALL');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $root . 'include/engine/functions.php';
require_once $root . 'include/engine/request.php';
require_once $root . 'include/engine/auth.php';
require_once $root . 'include/engine/template.php';
require_once $root . 'controllers/default.php';



