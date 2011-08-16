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

namespace ManiaLive\Application;

use ManiaLive\Utilities\Logger;
use ManiaLive\Utilities\Console;

abstract class ErrorHandling
{
	static $errorCount = 0;

	/**
	 * Counts number of errors that have been thrown
	 * and stops the application at a certain amount.
	 */
	public static function increaseErrorCount()
	{
		self::$errorCount++;

		// worst case, the application has reported maximal possible number of errors
		$config = \ManiaLive\Config\Config::getInstance();
		if ($config->maxErrorCount !== false && self::$errorCount > $config->maxErrorCount)
			die();
	}

	/**
	 * Takes a php error and converts it into an exception.
	 * @param integer $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param integer $errline
	 * @throws \ErrorException
	 */
	public static function createExceptionFromError($errno, $errstr, $errfile, $errline)
	{
		throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
	}

	/**
	 * Process an exception and decides what to do with it.
	 * @param \Exception $e
	 */
	public static function processRuntimeException(\Exception $e)
	{
		self::increaseErrorCount();
		self::displayAndLogError($e);
	}

	/**
	 * Process an exception and decides what to do with it.
	 * @param \Exception $e
	 */
	public static function processModuleException(\Exception $e)
	{
		// will cause program to quit in any case
		if ($e instanceof FatalException)
		{
			throw $e;
		}

		// throw exception again to be caught by upper module exception hander
		elseif ($e instanceof CriticalEventException)
		{
			throw $e;
		}

		// display message and continue if possible
		else
		{
			self::increaseErrorCount();
			self::displayAndLogError($e);
		}
	}

	/**
	 * This will stop an event from being processed!
	 * @param \Exception $e
	 */
	public static function processEventException(\Exception $e)
	{
		if ($e instanceof CriticalEventException)
		{
			if (!($e instanceof SilentCriticalEventException))
			{
				self::increaseErrorCount();
				self::displayAndLogError($e);
			}
		}

		// anything else, this normally should(!) be a fatalexception ...
		else
		{
			throw $e;
		}
	}

	/**
	 * Writes error message into the standard log file and also
	 * prints it to the console window.
	 * @param \Exception $e
	 */
	public static function displayAndLogError(\Exception $e)
	{
		$log = APP_NL . '    Occured on ' . date("d.m.Y") . ' at ' . date("H:i:s") . ' at process with ID #' . getmypid() . APP_NL;
		$log .= '    ---------------------------------' . APP_NL;
		Console::println('');
		foreach (self::computeMessage($e) as $line)
		{
			$log .= $line . APP_NL;
			Console::println(wordwrap($line, 73, APP_NL.'      ', true));
		}
		Console::println('');

		Logger::getLog('Error')->write($log);

		// write into global error log if config says so
		if (\ManiaLive\Config\Config::getInstance()->globalErrorLog)
			error_log($log, 3, APP_ROOT.'logs'.DIRECTORY_SEPARATOR.'GlobalErrorLog.txt');
	}

	/**
	 * Process an exception and decides what to do with it.
	 * @param \Exception $e
	 */
	public static function processStartupException(\Exception $e)
	{
		$message = APP_NL;
		$message .=  'Critical startup error!';
		$message .=  APP_NL;
		foreach (self::computeMessage($e) as $line)
			$message .=  wordwrap($line, 73, APP_NL.'      ', true) . APP_NL;
		$message .=  APP_NL;

		// log and display error, then die!
		error_log($message, 3, APP_ROOT.'logs'.DIRECTORY_SEPARATOR.'ErrorLog_'.getmypid().'.txt');

		die($message);
	}

	/**
	 * Computes a human readable log message from any exception.
	 */
	static protected function computeMessage(\Exception $e)
	{
		$line = $e->getLine();
		$code = $e->getCode();
		$file = $e->getFile();
		$message = $e->getMessage();
		$trace = $e->getTraceAsString();

		$buffer = array();
		$buffer[] = ' -> ' . get_class($e) . ' with code ' . $code;
		$buffer[] = '    ' . $message;
		$buffer[] = '  - in ' . $file . ' on line ' . $line;
		$buffer[] = '  - Stack: ';

		$lines = explode("\n", $trace);
		foreach ($lines as $i => $line)
		{
			if ($i == 0)
			{
				$buffer[count($buffer)-1] .= $line;
			}
			else
			{
				$line = '           '.$line;
				$buffer[] = $line;
			}
		}

		return $buffer;
	}
}

class FatalException extends \Exception {}
class CriticalEventException extends \Exception {}
class SilentCriticalEventException extends CriticalEventException {}
?>