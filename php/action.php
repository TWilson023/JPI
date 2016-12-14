<?php

require_once (__DIR__ . "/jpi.php");

$files = glob(__DIR__ . "/actions/*.php");

foreach ($files as $file)
    include_once ($file);

$jpi = JPI::getInstance();

if (isset($_POST['action'])) {
    $params = array();
    $output = array();

    if (isset($_POST['params'])) {
        $params = $_POST['params'];
    }

    try {
        $output = $jpi->performAction($_POST['action'], $params);
    } catch (Exception $e) {
        $output = array(
            'success' => false,
            'message' => "Error:  " . $e->getMessage() . " (" . $e->getCode() . ")"
        );
    }

    echo (json_encode($output));
} else {
    echo (json_encode(array(
        'success' => false,
        'message' => "No action specified"
    )));
}