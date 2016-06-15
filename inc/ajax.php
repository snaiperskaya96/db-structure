<?php
require_once('structure.php');
$structure = new Structure('../');

if(!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
    die('No direct access.');
}

$data = !empty($_REQUEST['database']) ? $_REQUEST['database'] : '';
if($data != ''){
    echo $structure->parseTable($data);
}

$data = !empty($_REQUEST['json']) ? $_REQUEST['json'] : '';
if($data != ''){
    echo $structure->toDoc($data);
}