<?php

$_GET['speaker'] = 'daril-gona';
$_GET['paged'] = '3';
$_REQUEST['speaker'] = 'daril-gona';
$_REQUEST['paged'] = '3';
$_SERVER['QUERY_STRING'] = 'speaker=daril-gona&paged=3';

require dirname(dirname(dirname(dirname(__DIR__)))) . '/index.php';
