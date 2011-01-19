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
		$command->help = 'Display all visible commands to a player it takes no parameter';
		$command->callback = array($this, 'help');

		$this->register($command);

		$command = new Command('man', 1);
		$command->addLoginAsFirstParameter = true;
		$command->help = 'Display help for every commands you give as parameter'."\n".
		'exemple of usage: /man man';
		$command->log = false;
		$command->callback = array($this, 'man');

		$this->register($command);

		$command = new Command('man', 2);
		$command->addLoginAsFirstParameter = true;
		$command->help = 'Display help for the command with the corresponding parameters'."\n".
		'exemple of usage: /man man 2';
		$command->log = false;
		$command->callback = array($this, 'man');

		$this->register($command);
	}

	function register(Command $command)
	{
		if($this->isRegistered($command->name, $command->parametersCount) == 2)
		{
			throw new CommandAlreadyRegisteredException($command->name.'|'.$command->parametersCount);
		}

		$this->registeredCommands[strtolower($command->name)][$command->parametersCount] = $command;
	}

	function isRegistered($commandName, $parametersCount)
	{
		if(isset($this->registeredCommands[strtolower($commandName)]))
		{
			return (isset($this->registeredCommands[strtolower($commandName)][$parametersCount]) ? 2 : 1);
		}
		return 0;
	}

	function getRegisteredCommands()
	{
		$commands = array();
		foreach ($this->registeredCommands as $commandName => $value)
		{
			$commands[$commandName] = array();
			foreach ($value as $parametersCount => $command)
			{
				$commands[$commandName][$parametersCount] = clone $command;
			}
		}
		return $commands;
	}

	function unregister(Command $command)
	{
		if($this->isRegistered($command->name, $command->parametersCount) == 2
		&& $this->registeredCommands[$command->name][$command->parametersCount] === $command)
		{
			unset($this->registeredCommands[$command->name][$command->parametersCount]);
			if(!$this->registeredCommands[$command->name])
			{
				unset($this->registeredCommands[$command->name]);
			}
			unset($command);
		}
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
				{
					$parameters[] = $tmpResult[$i+1];
				}
			}
				
			if($parameters)
			{
				$command = substr(array_shift($parameters), 1);
				$isRegistered = $this->isRegistered($command, count($parameters));
				if($isRegistered == 2)
				{
					$commandObject = $this->registeredCommands[strtolower($command)][count($parameters)];
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
				elseif ($isRegistered == 1)
				{
					$message = 'The command you entered exists but has not the correct number of parameters';
					Connection::getInstance()->chatSendServerMessage($message, Storage::getInstance()->getPlayerObject($login), true);
					$this->man($login, $command, -1);
				}
				elseif($command !== 'version')
				{
					$player = Storage::getInstance()->getPlayerObject($login);
					if ($player)
					{
						Connection::getInstance()->chatSendServerMessage(
							'Command $<$o$FC4'.$command.'$> does not exist, try /help to see a list of the available commands.', 
						Storage::getInstance()->getPlayerObject($login), true);
					}
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

	function man($login, $commandName, $parametersCount = -1)
	{
		$commandName = strtolower($commandName);
		$receiver = Storage::getInstance()->getPlayerObject($login);
		if($parametersCount == -1 && isset($this->registeredCommands[$commandName]))
		{
			$help = array();
			$help[] = 'Available $<$o$FC4'.$commandName.'$> commands:';
			foreach ($this->registeredCommands[$commandName] as $command)
			{
				if(!count($command->authorizedLogin) || in_array($login, $command->authorizedLogin))
				{
					$help[] = '$<$o$FC4'.$command->name.' ('.$command->parametersCount.')$>'.($command->help ? ':'.$command->help : '');
				}
			}
			$text = implode("\n", $help);
		}
		elseif(isset($this->registeredCommands[$commandName][$parametersCount]))
		{
			$command = $this->registeredCommands[$commandName][$parametersCount];
			if(!count($command->authorizedLogin) || in_array($login, $command->authorizedLogin))
			{
				$text = 'man page for command $<$o$FC4'.$command->name.' ('.$parametersCount.')$>'.($command->help ? "\n".$command->help : '');
			}
			else
			{
				$text = 'This command does not exists use help to see available commands';
			}
		}
		else
		{
			$text = 'This command does not exists use help to see available commands';
		}

		Connection::getInstance()->chatSendServerMessage($text, $receiver, true);
	}

	function onPlayerConnect($login, $isSpectator) {}
	function onPlayerDisconnect($login) {}
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