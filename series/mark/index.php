<?php

$_GET['series'] = 'mark';
$_REQUEST['series'] = 'mark';
$_SERVER['QUERY_STRING'] = 'series=mark';

require dirname(dirname(__DIR__)) . '/index.php';
