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

use ManiaLive\Event\Dispatcher;
use ManiaLive\DedicatedApi\Callback\Listener as ServerListener;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use DedicatedApi\Connection;
use ManiaLive\Utilities\Logger;

final class Interpreter extends \ManiaLib\Utils\Singleton implements ServerListener
{
	const NOT_REGISTERED_AT_ALL     = 0;
	const REGISTERED_DIFFERENTLY    = 1;
	const REGISTERED_AS_POLYMORPHIC = 2;
	const REGISTERED_EXACTLY        = 3;

	private $registeredCommands = array();

	/**
	 * @var Connection
	 */
	private $connection;

	protected function __construct()
	{
		Dispatcher::register(ServerEvent::getClass(), $this, ServerEvent::ON_PLAYER_CHAT);

		$command = new Command('help', 0);
		$command->addLoginAsFirstParameter = true;
		$command->log = false;
		$command->help = 'Display all visible commands to a player it takes no parameter';
		$command->callback = array($this, 'help');

		$this->register($command);

		$command = new Command('man', 1);
		$command->addLoginAsFirstParameter = true;
		$command->help = 'Display help for every commands you give as parameter'."\n".'exemple of usage: /man man';
		$command->isPublic = true;
		$command->log = false;
		$command->callback = array($this, 'man');

		$this->register($command);

		$command = new Command('man', 2);
		$command->addLoginAsFirstParameter = true;
		$command->help = 'Display help for the command with the corresponding parameters'."\n".'exemple of usage: /man man 2';
		$command->isPublic = true;
		$command->log = false;
		$command->callback = array($this, 'man');

		$this->register($command);

		$config = \ManiaLive\DedicatedApi\Config::getInstance();
		$this->connection = Connection::factory($config->host, $config->port, $config->timeout, $config->user, $config->password);
	}

	/**
	 * Use this method to register a new Command
	 * Once register the command can be used
	 * @param \ManiaLive\Features\ChatCommand\Command $command
	 */
	function register(Command $command)
	{
		list($requiredCount, $totalCount) = $this->getCommandParametersCount($command);
		$commandName = strtolower($command->name);
		$count = $requiredCount;
		try
		{
			if($command->parametersCount == -1 && isset($this->registeredCommands[$commandName]))
				throw new CommandAlreadyRegisteredException($command->name.'|'.$command->parametersCount);
			do
			{
				if($this->isRegistered($commandName, $count) > self::REGISTERED_DIFFERENTLY)
					throw new CommandAlreadyRegisteredException($command->name.'|'.$command->parametersCount);
				$this->registeredCommands[$commandName][$count] = $command;
			}
			while($count++ < $totalCount);
		}
		catch(\Exception $e)
		{
			while(--$count >= $requiredCount)
				unset($this->registeredCommands[$commandName][$count]);
			throw $e;
		}
	}

	/**
	 * Check if the given command with the number of argument given exists
	 * @param string $commandName the name of the command
	 * @param int $parametersCount the number of argument
	 * @return integer
	 */
	function isRegistered($commandName, $parametersCount = -2)
	{
		$commandName = strtolower($commandName);
		if(isset($this->registeredCommands[$commandName]))
		{
			if(isset($this->registeredCommands[$commandName][$parametersCount]))
				return self::REGISTERED_EXACTLY;
			else if(isset($this->registeredCommands[$commandName][-1]))
				return self::REGISTERED_AS_POLYMORPHIC;
			else
				return self::REGISTERED_DIFFERENTLY;
		}
		return self::NOT_REGISTERED_AT_ALL;
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
				$commands[$commandName][$parametersCount] = clone $command;
		}
		return $commands;
	}

	/**
	 * Unregister the given Command
	 * Once unregistered a command is no more available
	 * @param Command $command
	 */
	function unregister(Command $command)
	{
		list($requiredCount, $totalCount) = $this->getCommandParametersCount($command);
		$commandName = strtolower($command->name);
		$count = $requiredCount;
		do
		{
			if($this->isRegistered($commandName, $count) == self::REGISTERED_EXACTLY
					&& $this->registeredCommands[$commandName][$count] === $command)
				unset($this->registeredCommands[$commandName][$count]);
		}
		while($count++ < $totalCount);
		if(!$this->registeredCommands[$commandName])
			unset($this->registeredCommands[$commandName]);
	}

	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
	{
		if(!$isRegistredCmd)
			return;

		$cmdAndArgs = explode(' ', $text, 2);
		$command = substr($cmdAndArgs[0], 1);
		$parameters = count($cmdAndArgs) > 1 ? $cmdAndArgs[1] : '';

		if($command === 'version')
			return;

		$isRegistered = $this->isRegistered($command);
		if($isRegistered == self::REGISTERED_AS_POLYMORPHIC)
			$this->callCommand($login, $text, $command, $parameters ? array($parameters) : array(), true);
		else if($isRegistered == self::NOT_REGISTERED_AT_ALL)
			$this->connection->chatSendServerMessage(
					'Command $<$o$FC4'.$command.'$> does not exist, try /help to see a list of the available commands.',
					$login, true);
		else
		{
			if(strlen($parameters))
			{
				$matches = array();
				preg_match_all('/(?!\\\\)"((?:\\\\"|[^"])+)"?|([^\s]+)/', $parameters, $matches);
				$parameters = array_map(
						function($str, $word) { return str_replace('\\"', '"', $str != '' ? $str : $word); },
						$matches[1], $matches[2]);
			}
			else
				$parameters = array();

			$isRegistered = $this->isRegistered($command, count($parameters));
			if($isRegistered == self::REGISTERED_EXACTLY)
				$this->callCommand($login, $text, $command, $parameters);
			else
			{
				$this->connection->chatSendServerMessage(
						'The command you entered exists but has not the correct number of parameters, use $<$o$FC4/man '.$command.'$> for more details',
						$login, true);
				$this->man($login, $command);
			}
		}
	}

	private function callCommand($login, $text, $commandName, $parameters = array(), $polymorphicCommand = false)
	{
		$command = $this->registeredCommands[strtolower($commandName)][$polymorphicCommand ? -1 : count($parameters)];

		if(!count($command->authorizedLogin) || in_array($login, $command->authorizedLogin))
		{
			if($command->log)
				Logger::info('[ChatCommand from '.$login.'] '.$text);

			if($command->addLoginAsFirstParameter)
				array_unshift($parameters, $login);

			call_user_func_array($command->callback, $parameters);
		}
		else
			$this->connection->chatSendServerMessage(
					'$f00You are not authorized to use this command!', $login, true);
	}

	function help($login)
	{
		$availableCommands = array();
		foreach($this->registeredCommands as $commands)
			foreach($commands as $command)
				if($command->isPublic && (!count($command->authorizedLogin) || in_array($login, $command->authorizedLogin)))
				{
					$availableCommands[] = $command->name;
					continue 2;
				}

		if(count($availableCommands))
			$this->connection->chatSendServerMessage(
					'Available commands are: '.implode(', ', $availableCommands), $login, true);
		else
			$this->connection->chatSendServerMessage(
					'There is no command available', $login, true);
	}

	function man($login, $commandName, $parametersCount = -2)
	{
		$commandName = strtolower($commandName);
		if($parametersCount == -2 && isset($this->registeredCommands[$commandName]))
		{
			$help = array();
			$help[] = 'Available $<$o$FC4'.$commandName.'$> commands:';
			foreach($this->registeredCommands[$commandName] as $command)
			{
				if(!count($command->authorizedLogin) || in_array($login, $command->authorizedLogin))
				{
					$help[] = '$<$o$FC4'.$command->name.' ('.$command->parametersCount.')$>'.
							($command->help ? ':'.$command->help : '');
				}
			}
			$text = implode("\n", $help);
		}
		else if(isset($this->registeredCommands[$commandName][$parametersCount]))
		{
			$command = $this->registeredCommands[$commandName][$parametersCount];
			if(!count($command->authorizedLogin) || in_array($login, $command->authorizedLogin))
				$text = '$<$o$FC4'.$command->name.' ('.$command->parametersCount.')$>'.
						($command->help ? ':'.$command->help : '');
			else
				$text = 'This command does not exists use help to see available commands';
		}
		else
			$text = 'This command does not exists use help to see available commands';

		$this->connection->chatSendServerMessage($text, $login, true);
	}

	private function getCommandParametersCount(Command $command)
	{
		if($command->parametersCount !== null)
			return array($command->parametersCount, $command->parametersCount);
		else
		{
			if(is_array($command->callback))
				list($class, $method) = $command->callback;
			else
				list($class, $method) = explode('::', $command->callback);

			$reflection = new \ReflectionMethod($class, $method);

			$requiredCount = $reflection->getNumberOfRequiredParameters();
			$totalCount = $reflection->getNumberOfParameters();

			if($command->addLoginAsFirstParameter)
				return array($requiredCount-1, $totalCount-1);
			return array($requiredCount, $totalCount);
		}
	}

	function onPlayerConnect($login, $isSpectator) {}
	function onPlayerDisconnect($login, $disconnectionReason) {}
	function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries) {}
	function onEcho($internal, $public) {}
	function onServerStart() {}
	function onServerStop() {}
	function onBeginMatch() {}
	function onEndMatch($rankings, $winnerTeamOrMap) {}
	function onBeginMap($map, $warmUp, $matchContinuation) {}
	function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap) {}
	function onBeginRound() {}
	function onEndRound() {}
	function onStatusChanged($statusCode, $statusName) {}
	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex) {}
	function onPlayerFinish($playerUid, $login, $timeOrScore) {}
	function onPlayerIncoherence($playerUid, $login) {}
	function onBillUpdated($billId, $state, $stateName, $transactionId) {}
	function onTunnelDataReceived($playerUid, $login, $data) {}
	function onMapListModified($curMapIndex, $nextMapIndex, $isListModified) {}
	function onPlayerInfoChanged($playerInfo) {}
	function onManualFlowControlTransition($transition) {}
	function onVoteUpdated($stateName, $login, $cmdName, $cmdParam) {}
	function onModeScriptCallback($param1, $param2) {}
	function onPlayerAlliesChanged($login) {}
}

class CommandAlreadyRegisteredException extends \Exception {}

?>
