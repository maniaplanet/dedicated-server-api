<?php
/**
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace ManiaLivePlugins\Standard\InstanceLimiter;

class InstanceLimiter extends \ManiaLive\PluginHandler\Plugin
{
	protected $fp;
	
	function onInit()
	{
		$this->getLock();
	}
	
	function onUnload()
	{
		$this->releaseLock();
	}
	
	protected function getLockName()
	{
		return $this->storage->serverLogin;
	}
	
	protected function getLock()
	{
		$this->fp = fopen(sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->getLockName(), "w+");
		
		if(!flock($this->fp, LOCK_EX | LOCK_NB))
		{ 
			die(); //Maybe find something less hard
			throw new \ManiaLive\Application\FatalException("Can't get lock. ManiaLive is already started for this process.");
		}
	}
	
	protected function releaseLock()
	{
		flock($this->fp, LOCK_UN);
		fclose($this->fp);
	}
}
?>