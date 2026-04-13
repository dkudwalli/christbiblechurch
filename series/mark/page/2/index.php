<?php

$_GET['series'] = 'mark';
$_GET['paged'] = '2';
$_REQUEST['series'] = 'mark';
$_REQUEST['paged'] = '2';
$_SERVER['QUERY_STRING'] = 'series=mark&paged=2';

require dirname(dirname(dirname(dirname(__DIR__)))) . '/index.php';
