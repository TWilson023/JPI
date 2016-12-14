# JPI
Javascript-PHP Interface

**Still in early development - probably shouldn't be used in production code**

## What does it do?

JPI enables simple and straightforward communication between Javascript and PHP to **remove the need to write boring code** for HTTP requests and POST variable handling.

JPI also includes a `DBInterface` class for executing simple MySQL queries without touching any SQL.  Database connection settings can be set in `db-config.php`

## Usage

1. Create a class file in the `actions/` directory which extends `Action`:

  ```php
  require_once (__DIR__ . "/../jpi.php");

  $jpi = JPI::getInstance();

  class MyAction extends Action {

      public function __construct() {
          parent::__construct("MyAction"); // The registered name of the action
          
          // Check session variables, etc. if necessary
          $this->setAuthenticated(true);
      }

      public function run($params) {
          // Perform action ($params is an associative array of parameters specified in JS)
          return array(
            'hello': 'Good morning, ' . $params['name'],
            'goodbye' : 'Good night, ' . $params['name'] 
          );
      }

  }
  
  // Register an instance of the action
  $jpi->registerAction(new MyAction());
  ```
  
2. Use JS to call action
  
  ```javascript
  // Initialize JPI, and specify the relative location of action.php
  JPI.init("../php/action.php");
  
  var actionData = {
    name: "Tim"
  };
  
  // Perform the action
  JPI.performAction('MyAction', actionData, function(result) {
      // Use result
      var element =  document.createElement("h1")
      element.id = "result";
      element.innerHTML = result.hello;
      document.body.appendChild(element);
  });
  ```
  
That's it!  Much easier than dealing with jQuery or XMLHttpRequests.
