<?php

namespace ManiaLive\Features\ChatCommand;

use ManiaLive\Utilities\Logger;

use ManiaLive\Data\Storage;

use ManiaLive\DedicatedApi\Connection;
use ManiaLive\Utilities\Console;
use ManiaLive\Utilities\Singleton;
use ManiaLive\Event\Dispatcher;

class Interpreter extends Singleton implements \ManiaLive\DedicatedApi\Callback\Listener
{
	protected $registeredCommands = array();

	/**
	 * @return \ManiaLive\Features\ChatCommand\Interpreter
	 */
	static function getInstance()
	{
		return parent::getInstance();
	}
	
	protected function __construct()
	{
		Dispatcher::register(\ManiaLive\DedicatedApi\Callback\Event::getClass(), $this);

		$command = new Command('help', 0);
		$command->addLoginAsFirstParameter = true;
		$command->log = false;
		$command->callback = array($this, 'help');
		
		$this->register($command);
		
		$command = new Command('man', 2);
		$command->addLoginAsFirstParameter = true;
		$command->log = false;
		$command->callback = array($this, 'man');

		$this->register($command);
	}

	function register(Command $command)
	{
		if($this->isRegistered($command->name, $command->parametersCount))
		{
			throw new CommandAlreadyRegisteredException($command->name.'|'.$command->parametersCount);
		}

		$this->registeredCommands[$command->name][$command->parametersCount] = $command;
	}

	function isRegistered($commandName, $parametersCount)
	{
		return isset($this->registeredCommands[$commandName][$parametersCount]);
	}

	function getRegisteredCommands()
	{
		return $this->registeredCommands;
	}
	
	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
	{
		// TODO Handle params such as "a string with spaces"
		// TODO Implement param check
		// TODO Implement is not public handling
		if($isRegistredCmd)
		{
			$tmpResult = explode('"',$text);
			$parameters = array();
			for($i = 0; $i < count($tmpResult); $i += 2)
			{
				$tmp = explode(' ',$tmpResult[$i]);
				foreach($tmp as $temp)
				{
					if($temp !== '' && $temp !== ' ')
					{
						$parameters[] = $temp;
					}
				}
				if($i + 1 < count($tmpResult))
				$parameters[] = $tmpResult[$i+1];
			}
			
			if($parameters)
			{
				$command = substr(array_shift($parameters), 1);
				if($this->isRegistered($command, count($parameters)))
				{
					$commandObject = $this->registeredCommands[$command][count($parameters)];
					if(count($parameters) == $commandObject->parametersCount && (!count($commandObject->authorizedLogin) || in_array($login, $commandObject->authorizedLogin)))
					{
						if($commandObject->log)
						{
							Logger::getLog('Command')->write('[ChatCommand from '.$login.'] '.$text);
						}
						
						if($commandObject->addLoginAsFirstParameter)
						{
							array_unshift($parameters, $login);
						}
						call_user_func_array($commandObject->callback, $parameters);
					}
				}
				elseif($command !== 'version')
				{
					$connection = Connection::getInstance();
					if (isset(Storage::getInstance()->players[$login]))
						$connection->chatSendServerMessage(
							"Command '$command' does not exist, try /help to see a list of the available commands.", 
							Storage::getInstance()->players[$login], true);
				}
			}
		}
	}

	function help($login)
	{
		$connection = Connection::getInstance();
		
		$commandeAvalaible = array();
		foreach ($this->registeredCommands as $commands)
		{
			foreach($commands as $argumentCount => $command)
			{
				if($command->isPublic && (!count($command->authorizedLogin) || in_array($login, $command->authorizedLogin)))
				{
					$commandeAvalaible[] = $command->name.' ('.$argumentCount.')'.($command->help ? ': '.$command->help : '');
				}
			}
		}
		
		$receiver = Storage::getInstance()->getPlayerObject($login);
		
		$connection->chatSendServerMessage('Available commands: '.implode(', ', $commandeAvalaible), $receiver, true);
	}
	
	function man($login, $commandName, $parametersCount)
	{
		$receiver = Storage::getInstance()->getPlayerObject($login);
		
		if(isset($this->registeredCommands[$commandName][$parametersCount]))
		{
			$command = $this->registeredCommands[$commandName][$parametersCount];
			if($command->isPublic && (!count($command->authorizedLogin) || in_array($login, $command->authorizedLogin)))
			{
				$text = 'man page for command '.$command->name.' ('.$parametersCount.')'.($command->help ? "\n".$command->help : '');
			}
			else
			{
				$text = 'This command does not exists use help to see available commands';
			}
		} 	
		else 
		$text = 'This command does not exists use help to see available commands';
		
		Connection::getInstance()->chatSendServerMessage($text, $receiver, true);
	}

	function onPlayerConnect($login, $isSpectator) {}
	function onPlayerDisconnect($login) {}
	// function onPlayerChat($playerUid, $login, $text, $isRegistredCmd) {}
	function onPlayerManialinkPageAnswer($playerUid, $login, $answer) {}
	function onEcho($internal, $public) {}
	function onServerStart() {}
	function onServerStop() {}
	function onBeginRace($challenge) {}
	function onEndRace($rankings, $challenge) {}
	function onBeginChallenge($challenge, $warmUp, $matchContinuation) {}
	function onEndChallenge($rankings, $challenge, $wasWarmUp, $matchContinuesOnNextChallenge, $restartChallenge) {}
	function onBeginRound() {}
	function onEndRound() {}
	function onStatusChanged($statusCode, $statusName) {}
	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex) {}
	function onPlayerFinish($playerUid, $login, $timeOrScore) {}
	function onPlayerIncoherence($playerUid, $login) {}
	function onBillUpdated($billId, $state, $stateName, $transactionId) {}
	function onTunnelDataReceived($playerUid, $login, $data) {}
	function onChallengeListModified($curChallengeIndex, $nextChallengeIndex, $isListModified) {}
	function onPlayerInfoChanged($playerInfo) {}
	function onManualFlowControlTransition($transition) {}
}

class CommandAlreadyRegisteredException extends \Exception {}

?>