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
use ManiaLive\Event\Dispatcher;

class Interpreter extends \ManiaLib\Utils\Singleton implements \ManiaLive\DedicatedApi\Callback\Listener
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
		$command->isPublic = true;
		$command->log = false;
		$command->callback = array($this, 'man');

		$this->register($command);

		$command = new Command('man', 2);
		$command->addLoginAsFirstParameter = true;
		$command->help = 'Display help for the command with the corresponding parameters'."\n".
			'exemple of usage: /man man 2';
		$command->isPublic = true;
		$command->log = false;
		$command->callback = array($this, 'man');

		$this->register($command);
	}

	/**
	 * Use this method to register a new Command
	 * Once register the command can be used
	 * @param \ManiaLive\Features\ChatCommand\Command $command
	 */
	function register(Command $command)
	{
		//Get the number of parameters
		list($requiredParametersCount, $parametersCount) = $this->getCommandParametersCount($command);
		//Now we can register the command for each count of parameters
		$increment = $requiredParametersCount;
		try
		{
			do
			{
				$isRegitered = $this->isRegistered($command->name, $command->parametersCount);
				if($isRegitered >= 2)
				{
					throw new CommandAlreadyRegisteredException($command->name.'|'.$command->parametersCount);
				}
				$this->registeredCommands[strtolower($command->name)][$increment] = $command;
			}
			while($increment++ < $parametersCount);
		}
		catch(\Exception $e)
		{
			for($i = $increment; $i >= $requiredParametersCount; $i--)
			{
				if(isset($this->registeredCommands[strtolower($command->name)][$i]) && $this->registeredCommands[strtolower($command->name)][$i] === $command)
				{
					unset($this->registeredCommands[strtolower($command->name)][$i]);
				}
			}
			throw $e;
		}
	}

	/**
	 * Check if the given command with the number of argument given exists
	 * @param string $commandName the name of the command
	 * @param int $parametersCount the number of argument
	 * @return bool
	 */
	function isRegistered($commandName, $parametersCount)
	{
		if(isset($this->registeredCommands[strtolower($commandName)]))
		{
			if(isset($this->registeredCommands[strtolower($commandName)][-1]))
			{
				return 3;
			}
			elseif(isset($this->registeredCommands[strtolower($commandName)][$parametersCount]))
			{
				return 2;
			}
			else
			{
				return 1;
			}
		}
		return 0;
	}

	/**
	 * Get the registerd Commands
	 * @return \ManiaLive\Features\ChatCommand\Command[] The list of registerd Command
	 */
	function getRegisteredCommands()
	{
		$commands = array();
		foreach($this->registeredCommands as $commandName => $value)
		{
			$commands[$commandName] = array();
			foreach($value as $parametersCount => $command)
			{
				$commands[$commandName][$parametersCount] = clone $command;
			}
		}
		return $commands;
	}

	/**
	 * Unregister the giver Command
	 * Once unregistered a command is no more available
	 * @param Command $command
	 */
	function unregister(Command $command)
	{
		list($requiredParametersCount, $parametersCount) = $this->getCommandParametersCount($command);
		$increment = $requiredParametersCount;
		do
		{
			if($this->isRegistered($command->name, $increment) == 2
				&& $this->registeredCommands[$command->name][$increment] === $command)
			{
				unset($this->registeredCommands[$command->name][$increment]);
				if(!$this->registeredCommands[$command->name])
				{
					unset($this->registeredCommands[$command->name]);
				}
				unset($command);
			}
		}
		while($increment++ < $parametersCount);
	}

	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
	{
		// TODO Handle params such as "a string with spaces"
		if($isRegistredCmd)
		{
			$tmpResult = explode('"', $text);
			$parameters = array();
			for($i = 0; $i < count($tmpResult); $i += 2)
			{
				$tmp = explode(' ', $tmpResult[$i]);
				foreach($tmp as $temp)
				{
					if($temp !== '' && $temp !== ' ')
					{
						$parameters[] = $temp;
					}
				}
				if($i + 1 < count($tmpResult))
				{
					$parameters[] = $tmpResult[$i + 1];
				}
			}

			if($parameters)
			{
				$command = substr(array_shift($parameters), 1);
				$isRegistered = $this->isRegistered($command, count($parameters));
				if($isRegistered == 2)
				{
					$this->callCommand($login, $text, $command, $parameters);
				}
				elseif($isRegistered == 1)
				{
					$message = 'The command you entered exists but has not the correct number of parameters, use $<$o$FC4/man '.$command.'$> for more details';
					Connection::getInstance()->chatSendServerMessage($message,
						Storage::getInstance()->getPlayerObject($login), true);
					$this->man($login, $command, -1);
				}
				elseif($isRegistered == 3)
				{
					$this->callCommand($login, $text, $command, $parameters, true);
				}
				elseif($command !== 'version')
				{
					$player = Storage::getInstance()->getPlayerObject($login);
					if($player)
					{
						Connection::getInstance()->chatSendServerMessage(
							'Command $<$o$FC4'.$command.'$> does not exist, try /help to see a list of the available commands.',
							Storage::getInstance()->getPlayerObject($login), true);
					}
				}
			}
		}
	}

	protected function callCommand($login, $text, $command, $parameters = array(),
		$polymorphicCommand = false)
	{
		if(!$polymorphicCommand)
		{
			$commandObject = $this->registeredCommands[strtolower($command)][count($parameters)];
		}
		else
		{
			$commandObject = $this->registeredCommands[strtolower($command)][-1];
		}

		if((!count($commandObject->authorizedLogin) || in_array($login,
				$commandObject->authorizedLogin)))
		{
			if($commandObject->log)
			{
				Logger::getLog('Command')->write('[ChatCommand from '.$login.'] '.$text.APP_NL);
			}

			if($commandObject->addLoginAsFirstParameter)
			{
				array_unshift($parameters, $login);
			}
			call_user_func_array($commandObject->callback, $parameters);
		}
		else
		{
			Connection::getInstance()->chatSendServerMessage('$f00You are not authorized to use this command!',
				Storage::getInstance()->getPlayerObject($login), true);
		}
	}

	function help($login)
	{
		$connection = Connection::getInstance();

		$commandeAvalaible = array();
		foreach($this->registeredCommands as $commands)
		{
			foreach($commands as $argumentCount => $command)
			{
				if($command->isPublic && (!count($command->authorizedLogin) || in_array($login,
						$command->authorizedLogin)))
				{
					if(!in_array($command->name, $commandeAvalaible))
					{
						$commandeAvalaible[] = $command->name;
					}
				}
			}
		}

		$receiver = Storage::getInstance()->getPlayerObject($login);
		if(count($commandeAvalaible))
		{
			$connection->chatSendServerMessage('Available commands are: '.implode(', ',
					$commandeAvalaible), $receiver, true);
		}
		else
		{
			$connection->chatSendServerMessage('There is no command available',
				$receiver, true);
		}
	}

	function man($login, $commandName, $parametersCount = -1)
	{
		$commandName = strtolower($commandName);
		$receiver = Storage::getInstance()->getPlayerObject($login);
		if($parametersCount == -1 && isset($this->registeredCommands[$commandName]))
		{
			$help = array();
			$help[] = 'Available $<$o$FC4'.$commandName.'$> commands:';
			foreach($this->registeredCommands[$commandName] as $command)
			{
				if(!count($command->authorizedLogin) || in_array($login,
						$command->authorizedLogin))
				{
					$help[] = '$<$o$FC4'.$command->name.' ('.$command->parametersCount.')$>'.($command->help
								? ':'.$command->help : '');
				}
			}
			$text = implode("\n", $help);
		}
		elseif(isset($this->registeredCommands[$commandName][$parametersCount]))
		{
			$command = $this->registeredCommands[$commandName][$parametersCount];
			if(!count($command->authorizedLogin) || in_array($login,
					$command->authorizedLogin))
			{
				$text = 'man page for command $<$o$FC4'.$command->name.' ('.$parametersCount.')$>'.($command->help
							? "\n".$command->help : '');
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

	protected function getCommandParametersCount(Command $command)
	{
		if($command->parametersCount !== null)
		{
			return array($command->parametersCount, $command->parametersCount);
		}
		else
		{
			if(is_array($command->callback))
			{
				$className = $command->callback[0];
				$method = $command->callback[1];
			}
			else
			{
				$callback = explode('::', $command->callback);
				$className = $callback[0];
				$method = $callback[1];
			}
			if($className != '')
			{
				$reflection = new \ReflectionMethod($className, $method);
			}
			else
			{
				$reflection = new \ReflectionFunction($method);
			}
			$requiredParametersCount = ($command->addLoginAsFirstParameter ? $reflection->getNumberOfRequiredParameters() - 1
						: $reflection->getNumberOfRequiredParameters());
			$parametersCount = ($command->addLoginAsFirstParameter ? $reflection->getNumberOfParameters() - 1
						: $reflection->getNumberOfParameters());
			return array($requiredParametersCount, $parametersCount);
		}
	}

	function onPlayerConnect($login, $isSpectator)
	{
		
	}

	function onPlayerDisconnect($login)
	{
		
	}

	function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries)
	{
		
	}

	function onEcho($internal, $public)
	{
		
	}

	function onServerStart()
	{
		
	}

	function onServerStop()
	{
		
	}

	function onBeginRace($challenge)
	{
		
	}

	function onEndRace($rankings, $challenge)
	{
		
	}

	function onBeginChallenge($challenge, $warmUp, $matchContinuation)
	{
		
	}

	function onEndChallenge($rankings, $challenge, $wasWarmUp,
		$matchContinuesOnNextChallenge, $restartChallenge)
	{
		
	}

	function onBeginRound()
	{
		
	}

	function onEndRound()
	{
		
	}

	function onStatusChanged($statusCode, $statusName)
	{
		
	}

	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap,
		$checkpointIndex)
	{
		
	}

	function onPlayerFinish($playerUid, $login, $timeOrScore)
	{
		
	}

	function onPlayerIncoherence($playerUid, $login)
	{
		
	}

	function onBillUpdated($billId, $state, $stateName, $transactionId)
	{
		
	}

	function onTunnelDataReceived($playerUid, $login, $data)
	{
		
	}

	function onChallengeListModified($curChallengeIndex, $nextChallengeIndex,
		$isListModified)
	{
		
	}

	function onPlayerInfoChanged($playerInfo)
	{
		
	}

	function onManualFlowControlTransition($transition)
	{
		
	}

	function onVoteUpdated($stateName, $login, $cmdName, $cmdParam)
	{
		
	}
	
	function onRulesScriptCallback($param1, $param2)
	{
		
	}
}

class CommandAlreadyRegisteredException extends \Exception
{
	
}

?>