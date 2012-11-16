<?php


$root = dirname(__FILE__) . '/../';
require_once $root . 'include/engine/app.php';
$app = new App($root);
$app->execute();


?>

