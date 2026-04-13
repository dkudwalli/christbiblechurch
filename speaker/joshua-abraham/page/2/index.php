<?php

$_GET['speaker'] = 'joshua-abraham';
$_GET['paged'] = '2';
$_REQUEST['speaker'] = 'joshua-abraham';
$_REQUEST['paged'] = '2';
$_SERVER['QUERY_STRING'] = 'speaker=joshua-abraham&paged=2';

require dirname(dirname(dirname(dirname(__DIR__)))) . '/index.php';
