<?php

$_GET['speaker'] = 'joshua-abraham';
$_REQUEST['speaker'] = 'joshua-abraham';
$_SERVER['QUERY_STRING'] = 'speaker=joshua-abraham';

require dirname(dirname(__DIR__)) . '/index.php';
