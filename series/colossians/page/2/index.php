<?php

$_GET['series'] = 'colossians';
$_GET['paged'] = '2';
$_REQUEST['series'] = 'colossians';
$_REQUEST['paged'] = '2';
$_SERVER['QUERY_STRING'] = 'series=colossians&paged=2';

require dirname(dirname(dirname(dirname(__DIR__)))) . '/index.php';
