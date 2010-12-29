<?php

use ManiaLive\Application\ErrorHandling;
use ManiaLive\Utilities\Console;

// enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// include the __autoload function ...
require_once __DIR__ . '/utils.inc.php';

ManiaLiveApplication\Application::getInstance()->run();
?>