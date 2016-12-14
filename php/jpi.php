<?php

error_reporting(0);
ini_set('display_errors', 0);

require_once (__DIR__ . "/DBInterface.php");

class ActionPerformException extends Exception {

    public static $ERRORS = array(
        'GENERIC_ERROR' => 0,
        'NOT_AUTHENTICATED' => 1,
        'INVALID_ACTION' => 2
    );

    public function __construct($message, $code = 0) {
        parent::__construct($message, $code);
    }

    public function __toString() {
        return __CLASS__ . ": [$this->code]: {$this->message}\n";
    }

}

abstract class Action {

    public $name, $authenticated;

    public function __construct($name) {
        $this->name = $name;
        $this->authenticated = false;
    }

    public function setAuthenticated($authenticated) {
        $this->authenticated = $authenticated;
    }

    public abstract function run($params);

}

/**
 * Class JPI
 * Manages functions and interfacing between JS and PHP/MySQL
 */
class JPI {

    public static $instance;

    public $dbi, $actions;

    /**
     * @return JPI Instance of JPI
     */
    public static function getInstance() {
        if (static::$instance === null) {

            $cfg = include (__DIR__ . "/db-config.php");
            static::$instance = new static($cfg['db_host'], $cfg['db_user'], $cfg['db_password'], $cfg['db_database']);

        }

        return static::$instance;
    }

    public function __construct($host, $user, $password, $db) {
        if ($host !== null) {
            $mysqli = new mysqli($host, $user, $password, $db);
            if ($mysqli->connect_errno)
                echo ("Failed to connect to MySQL server (" . $mysqli->connect_errno . ") - " . $mysqli->connect_error);

            $this->dbi = new DBInterface($mysqli);
        }
        $this->actions = array();
    }

    /**
     * Registers an action
     * @param Action $action
     */
    public function registerAction($action) {
        $this->actions[$action->name] = $action;
    }

    /**
     * Runs an action with specified parameters
     * @param $name
     * @param $params
     * @throws ActionPerformException
     * @return array Associative array with 'success', 'message', and 'data'
     */
    public function performAction($name, $params) {
        if (array_key_exists($name, $this->actions)) {
            $action = $this->actions[$name];
            if ($action->authenticated) {
                $result = array(
                    'success' => false
                );
                try {
                    $result['data'] = $action->run($params);
                    $result['success'] = true;
                    $result['message'] = "Action performed successfully.";
                } catch (Exception $e) {
                    $result['message'] = $e->getMessage();
                }
                return $result;
            } else {
                throw new ActionPerformException("Not authenticated",
                    ActionPerformException::$ERRORS['NOT_AUTHENTICATED']);
            }
        } else {
            throw new ActionPerformException("Action '$name' does not exist or has not yet been registered",
                ActionPerformException::$ERRORS['INVALID_ACTION']);
        }
    }

}