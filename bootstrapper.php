<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */


// enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// include the __autoload function ...
require_once __DIR__ . '/utils.inc.php';

ManiaLiveApplication\Application::getInstance()->run();
?>