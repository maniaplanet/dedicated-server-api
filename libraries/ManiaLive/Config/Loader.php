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

namespace ManiaLive\Config;

class Loader extends \ManiaLib\Utils\Singleton
{
	static $aliases = array(
		'config' => 'ManiaLive\\Config\\Config',
		'database' => 'ManiaLive\\Database\\Config',
		'wsapi' => 'ManiaLive\\Features\\WebServices\\Config',
		'manialive' => 'ManiaLive\\Application\\Config',
		'server' => 'ManiaLive\\DedicatedApi\\Config',
		'threading' => 'ManiaLive\\Threading\\Config',
	);
	
	protected $configFilename;
	protected $debugPrefix = '[CONFIG LOADER]';

	function setConfigFilename($configFilename)
	{
		$this->configFilename = $configFilename;
	}
	
	final public function run()
	{
		$mtime = microtime(true);
		$this->debug('Starting runtime load');
		$this->preLoad();
		$this->debug('Pre-load completed');
		$this->data = $this->load();
		$this->debug('Load completed');
		$this->debug("Data dump:\n\n".print_r($this->data, true));
		$mtime = microtime(true) - $mtime;
		$this->debug('Runtime load completed in '.number_format($mtime*1000, 2).' milliseconds');
		$this->postLoad();
	}
	
	protected function preLoad()
	{
		if(!file_exists($this->configFilename))
		{
			throw new \Exception($this->configFilename.' does not exist');
		}
	}
	
	protected function postLoad()
	{
		
	}
	
	/**
	 * @return \ManiaLive\Config\Config
	 */
	protected function load()
	{
		$values = $this->loadINI($this->configFilename);
		$this->debug($this->configFilename.' parsed');
		list($values, $overrides) = $this->scanOverrides($values);
		$values = $this->processOverrides($values, $overrides);
		$values = $this->loadAliases($values);
		$values = $this->replaceAliases($values);
		$instances = $this->arrayToSingletons($values);
		$this->debug(sprintf('Loaded %d class instances', count($instances)));
		return $instances;
	}
	
	/**
	 * @return array
	 */
	protected function loadINI($filename)
	{
		try 
		{
			return parse_ini_file($filename, true);
		}
		catch(Exception $e)
		{
			throw new Exception('Could not parse INI file: '.$e->getMessage());
		}
	}
	
	/**
	 * Creates two arrays (values and ovverides) from one array
	 * @return array(array values, array overrides)
	 */
	protected function scanOverrides(array $array)
	{
		$values = array();
		$overrides = array();
		
		foreach($array as $key => $value)
		{
			if(strstr($key, ':'))
			{
				$overrides[$key] = $value;
			}
			else
			{
				$values[$key] = $value;
			}
		}
		return array($values, $overrides);
	}
	
	/**
	 * Checks if the values from overrides actually match an ovveride rule, anb
	 * override teh values array if it's the case
	 * @return array
	 */
	protected function processOverrides(array $values, array $overrides)
	{
		if($overrides)
		{
			foreach($overrides as $key => $override)
			{
				$matches = null;
				if(preg_match('/^hostname: (.+)$/i', $key, $matches))
				{
					if($matches[1] == gethostname())
					{
						$this->debug('Found hostname override: '.$matches[1]);
						$values = $this->overrideArray($values, $override);
						break;
					}
				}
			}
		}
		return $values;
	}
	
	/**
	 * Overrides the values of the source array with values from teh overrride array
	 * It does not work with associate arrays
	 * @return array
	 */
	protected function overrideArray(array $source, array $override)
	{
		foreach($override as $key => $value)
		{
			$source[$key] = $value;
		}
		return $source;
	}
	
	/**
	 * @return array
	 */
	protected function loadAliases(array $values)
	{
		foreach ($values as $key => $value)
		{
			if(preg_match('/^\s*alias\s+(\S+)$/i', $key, $matches))
			{
				if(isset($matches[1]))
				{
					self::$aliases[$matches[1]] = $value;
					unset($values[$key]);
					$this->debug(sprintf('Found alias "%s"', $matches[1]));
				}
			}
		}
		return $values;
	}
	
	/**
	 * @return array
	 */
	protected function replaceAliases(array $values)
	{
		$newValues = array();
		foreach ($values as $key => $value)
		{
			$callback = explode('.', $key, 2);
			if(count($callback) == 2)
			{
				$className = reset($callback);
				$propertyName = end($callback);
				if(isset(self::$aliases[$className]))
				{
					$className = self::$aliases[$className];
				}
				$newValues[$className.'.'.$propertyName] = $value;
			}
			else
			{
				$newValues[$key] = $value;
			}
		}
		return $newValues;
	}
	
	/**
	 * @return array[Singleton]
	 */
	protected function arrayToSingletons($values)
	{
		$instances = array();
		foreach($values as $key => $value)
		{
			$callback = explode('.', $key, 2);
			if(count($callback) != 2)
			{
				$this->debug('Could not parse key='.$key);
				continue;
			}
			$className = reset($callback);
			$propertyName = end($callback);
			if(!class_exists($className))
			{
				$this->debug(sprintf('Class %s does not exists', $className));
				continue;
			}
			if(!is_subclass_of($className, '\\ManiaLib\\Utils\\Singleton'))
			{
				$this->debug(sprintf('Class %s must be an instance of \ManiaLib\Utils\Singleton', $className));
				continue;
			}
			if(!property_exists($className, $propertyName))
			{
				$this->debug(sprintf('%s::%s does not exists or is not public', $className, $propertyName));
				continue;
			}
			$instance = call_user_func(array($className, 'getInstance'));
			
			$instance->$propertyName = $value;
			$instances[$className] = $instance;
		}
		return $instances;
	}
	
	protected function debug($message)
	{
		error_log($this->debugPrefix.' '.$message.PHP_EOL, 3, APP_ROOT.'logs'.DIRECTORY_SEPARATOR.'Loader_'.getmypid().'.txt');
	}
}

?>