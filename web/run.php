<?php

/**
 * Script to execute requests from the source namespace. Should be called from the console.
 */

$_SERVER['QUERY_STRING'] = 'collect&start';
$_SERVER['HTTP_HOST'] = 'localhost';
$_GET['collect'] = '';
$_GET['start'] = '';
$_POST['namespace_source'] = 'r056';
$_POST['test_count'] = "1";
$_POST['namespace_target'] = $_POST['namespace_source'].'_'.microtime(true);
$_POST['description'] = '';
$_REQUEST = array_merge($_REQUEST, $_GET, $_POST); 

$root = dirname(__FILE__) . '/../';
require_once $root . 'include/engine/app.php';
$app = new App($root);
$app->execute();
