<?php

require_once (__DIR__ . "/../jpi.php");

$jpi = JPI::getInstance();

class TestAction extends Action {

    public function __construct() {
        parent::__construct("TestAction");
        $this->setAuthenticated(true);
    }

    public function run($params) {
        return array(
            'hello' => "Hello, world!",
            'repeat' => $params['repeat']
        );
    }

}

$jpi->registerAction(new TestAction());