<?php

$_GET['series'] = 'mark';
$_GET['paged'] = '3';
$_REQUEST['series'] = 'mark';
$_REQUEST['paged'] = '3';
$_SERVER['QUERY_STRING'] = 'series=mark&paged=3';

require dirname(dirname(dirname(dirname(__DIR__)))) . '/index.php';
