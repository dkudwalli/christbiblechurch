<?php

$_GET['speaker'] = 'daril-gona';
$_GET['paged'] = '2';
$_REQUEST['speaker'] = 'daril-gona';
$_REQUEST['paged'] = '2';
$_SERVER['QUERY_STRING'] = 'speaker=daril-gona&paged=2';

require dirname(dirname(dirname(dirname(__DIR__)))) . '/index.php';
