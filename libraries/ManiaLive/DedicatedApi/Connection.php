<?php
/**
 *
 * @author Philippe Melot
 * @copyright 2009-2010 NADEO
 * @package ManiaMod
 */

namespace ManiaLive\DedicatedApi;

use ManiaLive\DedicatedApi\Structures\Music;

use ManiaLive\DedicatedApi\Structures\Player;

use ManiaLive\DedicatedApi\Structures\Status;

use ManiaLive\DedicatedApi\Structures\Vote;

use ManiaLive\DedicatedApi\Structures\ServerOptions;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Utilities\Console;

/**
 * Dedicated Server Connection Instance
 * Methods returns nothing if $multicall = true
 * FIXME maybe rename Connection to resolve naming conflicts?
 * @subpackage DedicatedApi
 */
class Connection extends \ManiaLive\Utilities\Singleton
{
	/**
	 * XML-RPC server port
	 * @var int
	 */
	static public $port = 5000;
	/**
	 * XML-RPC server hostname
	 * @var string
	 */
	static public $hostname = '127.0.0.1';
	/**
	 * XML-RPC username (SuperAdmin, Admin or User)
	 * @var string
	 */
	static public $username = 'SuperAdmin';
	/**
	 * XML-RPC password
	 * @var string
	 */
	static public $password = 'SuperAdmin';
	/**
	 * XML-RPC client instance
	 * @var
	 */
	protected $xmlrpcClient;

	/**
	 * @return Connection
	 */
	static function getInstance()
	{
		return parent::getInstance();
	}

	/**
	 * Game Modes
     * TODO maybe put this somewhere else?
	 */
	const GAMEMODE_ROUNDS = 0;
	const GAMEMODE_TIMEATTACK = 1;
	const GAMEMODE_TEAM = 2;
	const GAMEMODE_LAPS = 3;
	const GAMEMODE_STUNTS = 4;
	const GAMEMODE_CUP = 5;
	
	/**
	 * Constructor of the class
	 * @param int $port represents the communication port
	 * @param string $hostname represents the ip to reach
	 * @param string $superAdminPassword represents the SuperAdmin password
	 * @param string $adminPassword represents the Admin password
	 * @param string $userPassword represents the User password
	 */
	protected function __construct()
	{
		$this->xmlrpcClient = new Xmlrpc\ClientMulticall_Gbx(self::$hostname, self::$port);
		Console::printlnFormatted('XML-RPC connection established');
		$this->authenticate(self::$username, self::$password);
		Console::printlnFormatted('Successfully authentified with XML-RPC server');
	}

	/**
	 * Close the current socket connexion
	 * Never call this method, use instead DedicatedApiFactory::deleteConnection($hostname,$port)
	 */
	function terminate()
	{
		$this->xmlrpcClient->Terminate();
	}

	/**
	 *
	 * Read a Call back on the DedicatedServer and call the method if handle
	 * @param array $methods if empty, every methods will be called on call back, otherwise only the method declared inside. The metho name must be the name of the interface's method
	 */
	function executeCallbacks()
	{
		$this->xmlrpcClient->readCallbacks();
		$calls = $this->xmlrpcClient->getCallbackResponses();
		if (!empty($calls))
		{
			foreach ($calls as $call)
			{
				$method = substr($call[0], 11); // remove trailing "TrackMania."
				$params = (array) $call[1];
				Dispatcher::dispatch(new Callback\Event($this, $method, $params));
			}
		}
	}

	/**
	 * Execute the calls in queue and return the result
	 * TODO Prendre en compte les retours du mutliQuery (via un handler ?)
	 */
	function executeMulticall()
	{
		$this->xmlrpcClient->multiqueryIgnoreResult();
	}

	/**
	 * Add a call in queur. It will be executed by the next Call from the user to executemulticall
	 * @param string $methodName
	 * @param string $authLevel
	 * @param array $params
	 */
	protected function execute($methodName, $params = array(), $multicall=false)
	{
		if($multicall)
		{
			$this->xmlrpcClient->addCall($methodName, $params);
		}
		else
		{
			array_unshift($params, $methodName);
			call_user_func_array(array($this->xmlrpcClient, 'query'), $params);
			return $this->xmlrpcClient->getResponse();
		}
	}
	
	/**
	 * Given the name of a method, return an array of legal signatures. 
	 * Each signature is an array of strings. 
	 * The first item of each signature is the return type, and any others items are parameter types.
	 * @param string $methodName
	 * @return array
	 */
	function methodSignature($methodName)
	{
		return $this->execute('system.methodSignature', array( $methodName ));
	}
	
	/**
	 * Change the password for the specified login/user.
	 * @param string $username
	 * @param string $password
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function changeAuthPassword($username, $password)
	{
		if(!is_string($password))
		throw new InvalidArgumentException('password = '.print_r($password,true));
		if($username != 'User' && $username != 'Admin' && $username != 'SuperAdmin')
		throw new InvalidArgumentException('username = '.print_r($username,true));

		return $this->execute(ucfirst(__FUNCTION__), array($username, $password), false);
	}

	/**
	 * Allow the GameServer to call you back.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 * @throws InvalidArgumentException
	 */
	function enableCallbacks($enable, $multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array((bool) $enable), $multicall);
	}

	/**
	 * Returns a struct with the Name, Version and Build of the application remotely controled.
	 * @return ManiaLive\DedicatedApi\Structures\Version
	 * @throws InvalidArgumentException
	 */
	function getVersion()
	{
		$result = $this->execute(ucfirst(__FUNCTION__));
		return Structures\Version::fromArray($result);
	}

	function authenticate($username, $password)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($username, $password), false);
	}

	/**
	 * Call a vote for a cmd. The command is a XML string corresponding to an XmlRpc request.
	 * You can additionally specifiy specific parameters for this vote: a ratio, a time out
	 * and who is voting. Special timeout values: a timeout of '0' means default, '1' means
	 * indefinite; a ratio of '-1' means default; Voters values: '0' means only active players,
	 * '1' means any player, '2' is for everybody, pure spectators included.
	 * @param ManiaLive\DedicatedApi\Structures\Vote $vote
	 * @param double $ratio -1 means default, else ration should be between 0 and 1
	 * @param int $timeout time to vote in millisecondes, '0' means default
	 * @param int $voters Voters values: '0' means only active players, '1' means any player, '2' is for everybody, pure spectators included
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 */
	function callVote(Vote $vote, $ratio = -1.0, $timeout = 0, $voters = 1, $multicall = false)
	{
		if(!($vote instanceof Vote))
		throw new InvalidArgumentException('cmd = '.print_r($vote,true));
		if(!is_double($ratio))
		throw new InvalidArgumentException('ratio = '.print_r($ratio,true));
		if(!is_int($timeout))
		throw new InvalidArgumentException('timeout = '.print_r($timeout,true));
		if(!is_int($voters))
		throw new InvalidArgumentException('voters = '.print_r($voters,true));

		$tmpCmd = new Xmlrpc\Request($vote->CmdName, array($vote->CmdParam));

		return  $this->execute(ucfirst(__FUNCTION__).'Ex', array($tmpCmd->getXml(), $ratio, $timeout, $voters), $multicall);
	}

	/**
	 * Call a vote to kick a player.
	 * You can additionally specifiy specific parameters for this vote: a ratio, a time out
	 * and who is voting. Special timeout values: a timeout of '0' means default, '1' means
	 * indefinite; a ratio of '-1' means default; Voters values: '0' means only active players,
	 * '1' means any player, '2' is for everybody, pure spectators included.
	 * @param Player $player
	 * @param double $ratio -1 means default, else ration should be between 0 and 1
	 * @param int $timeout time to vote in millisecondes, '0' means default
	 * @param int $voters Voters values: '0' means only active players, '1' means any player, '2' is for everybody, pure spectators included
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 */
	function callVoteKick(Player $player, $ratio = -1.0, $timeout = 0, $voters = 1, $multicall = false)
	{
		if(!($player instanceof Player))
		throw new InvalidArgumentException('player = '.print_r($player,true));
		if(!is_double($ratio))
		throw new InvalidArgumentException('ratio = '.print_r($ratio,true));
		if(!is_int($timeout))
		throw new InvalidArgumentException('timeout = '.print_r($timeout,true));
		if(!is_int($voters))
		throw new InvalidArgumentException('voters = '.print_r($voters,true));

		$tmpCmd = new Xmlrpc\Request('Kick', array($player->login));

		return $this->execute('CallVoteEx', array($tmpCmd->getXml(), $ratio, $timeout, $voters), $multicall);
	}

	/**
	 * Call a vote to ban a player.
	 * You can additionally specifiy specific parameters for this vote: a ratio, a time out
	 * and who is voting. Special timeout values: a timeout of '0' means default, '1' means
	 * indefinite; a ratio of '-1' means default; Voters values: '0' means only active players,
	 * '1' means any player, '2' is for everybody, pure spectators included.
	 * @param string $player
	 * @param double $ratio -1 means default, else ration should be between 0 and 1
	 * @param int $timeout time to vote in millisecondes, '0' means default
	 * @param int $voters Voters values: '0' means only active players, '1' means any player, '2' is for everybody, pure spectators included
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 */
	function callVoteBan(Player $player, $ratio = -1.0, $timeout = 0, $voters = 1, $multicall = false)
	{
		if(!($player instanceof Player))
		throw new InvalidArgumentException('player = '.print_r($player,true));
		if(!is_double($ratio))
		throw new InvalidArgumentException('ratio = '.print_r($ratio,true));
		if(!is_int($timeout))
		throw new InvalidArgumentException('timeout = '.print_r($timeout,true));
		if(!is_int($voters))
		throw new InvalidArgumentException('voters = '.print_r($voters,true));

		$tmpCmd = new Xmlrpc\Request('Ban', array($player->login));

		return $this->execute('CallVoteEx', array($tmpCmd->getXml(), $ratio, $timeout, $voters), $multicall);
	}

	/**
	 * Call a vote to restart the current Challenge.
	 * You can additionally specifiy specific parameters for this vote: a ratio, a time out
	 * and who is voting. Special timeout values: a timeout of '0' means default, '1' means
	 * indefinite; a ratio of '-1' means default; Voters values: '0' means only active players,
	 * '1' means any player, '2' is for everybody, pure spectators included.
	 * @param double $ratio -1 means default, else ration should be between 0 and 1
	 * @param int $timeout time to vote in millisecondes, '0' means default
	 * @param int $voters Voters values: '0' means only active players, '1' means any player, '2' is for everybody, pure spectators included
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 */
	function callVoteRestartChallenge($ratio = -1, $timeout = 0, $voters = 1, $multicall = false)
	{
		if(!is_double($ratio))
		throw new InvalidArgumentException('ratio = '.print_r($ratio,true));
		if(!is_int($timeout))
		throw new InvalidArgumentException('timeout = '.print_r($timeout,true));
		if(!is_int($voters))
		throw new InvalidArgumentException('voters = '.print_r($voters,true));

		$tmpCmd = new Xmlrpc\Request('ChallengeRestart', array());

		return $this->execute('CallVoteEx', array($tmpCmd->getXml(), $ratio, $timeout, $voters), $multicall);
	}

	/**
	 * Call a vote to go to the next Challenge.
	 * You can additionally specifiy specific parameters for this vote: a ratio, a time out
	 * and who is voting. Special timeout values: a timeout of '0' means default, '1' means
	 * indefinite; a ratio of '-1' means default; Voters values: '0' means only active players,
	 * '1' means any player, '2' is for everybody, pure spectators included.
	 * @param double $ratio -1 means default, else ration should be between 0 and 1
	 * @param int $timeout time to vote in millisecondes, '0' means default
	 * @param int $voters Voters values: '0' means only active players, '1' means any player, '2' is for everybody, pure spectators included
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 */
	function callVoteNextChallenge($ratio = -1, $timeout = 0, $voters = 1, $multicall = false)
	{
		if(!is_double($ratio))
		throw new InvalidArgumentException('ratio = '.print_r($ratio,true));
		if(!is_int($timeout))
		throw new InvalidArgumentException('timeout = '.print_r($timeout,true));
		if(!is_int($voters))
		throw new InvalidArgumentException('voters = '.print_r($voters,true));

		$tmpCmd = new Xmlrpc\Request('NextChallenge', array());

		return $this->execute('CallVoteEx', array($tmpCmd->getXml(), $ratio, $timeout, $voters), $multicall);
	}

	/**
	 * 	Used internaly by game.
	 *  @param bool $multicall
	 * 	@return bool
	 */
	protected function internalCallVote($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Cancel the current vote.
	 * @param bool $multicall
	 * @return bool
	 */
	function cancelVote($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the vote currently in progress.
	 * The returned structure is { CallerLogin, CmdName, CmdParam }.
	 * @return ManiaLive\DedicatedApi\Structures\Vote
	 */
	function getCurrentCallVote()
	{
		return Vote::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Set a new timeout for waiting for votes. A zero value disables callvote.
	 * Requires a challenge restart to be taken into account
	 * @param int $timeout time to vote in millisecondes, '0' disables callvote
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCallVoteTimeOut($timeout, $multicall = false)
	{
		if(!is_int($timeout))
		throw new InvalidArgumentException('timeout = '.print_r($timeout, true));

		return $this->execute(ucfirst(__FUNCTION__), array($timeout), $multicall);
	}

	/**
	 * Get the current and next timeout for waiting for votes.
	 * The struct returned contains two fields 'CurrentValue' and 'NextValue'.
	 * @return array
	 */
	function getCallVoteTimeOut()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new default ratio for passing a vote.
	 * Must lie between 0 and 1.
	 * @param double $ratio
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCallVoteRatio($ratio, $multicall = false)
	{
		if(!is_double($ratio) && ($ratio < 0 || $ratio > 1))
		throw new InvalidArgumentException('ratio = '.print_r($ratio, true));

		return $this->execute(ucfirst(__FUNCTION__), array($ratio), $multicall);
	}

	/**
	 * Get the current default ratio for passing a vote.
	 * This value lies between 0 and 1.
	 * @return double
	 */
	function getCallVoteRatio()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set new ratios for passing specific votes.
	 * The parameter is an array of struct
	 * {string votecommand, double ratio}, ratio is in [0,1] or -1 for vote disabled.
	 * @param array $ratios
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCallVoteRatios(array $ratios, $multicall = false)
	{
		if(!is_array($ratios))
		throw new InvalidArgumentException('ratio = '.print_r($ratio, true));

		for($i = 0; $i < count($ratios); $i++)
		{
			if(!is_array($ratios[$i]) && !array_key_exists('Command', $ratios[$i]) && !array_key_exists('Ratio', $ratios[$i]))
			throw new InvalidArgumentException('ratios['.$i.'] = '.print_r($ratio, true));
			if(!is_string($ratios[$i]['Command']))
			throw new InvalidArgumentException('ratios['.$i.'][Command] = '.print_r($ratios[$i][0],true));
			if(!is_double($ratios[$i]['Ratio']) && ($ratios[$i]['Ratio'] != -1 && ($ratios[$i]['Ratio'] < 0 || $ratios[$i]['Ratio'] > 1)))
			throw new InvalidArgumentException('ratios['.$i.'][Ratio] = '.print_r($ratios[$i]['Ratio'],true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($ratios), $multicall);
	}

	/**
	 * Get the current ratios for passing votes.
	 * @return array
	 */
	function getCallVoteRatios()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Send a localised text message to all clients without the server login.
	 * The parameter is an array of structures {Lang='??', Text='...'}.
	 * If no matching language is found, the last text in the array is used.
	 * @param array $messages
	 * @param Player|array[Player] $playerId
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatSendServerMessageToLanguage(array $messages, $receiver = null, $multicall = false)
	{
		if($receiver == null)
		{
			return $this->execute(ucfirst(__FUNCTION__), array($messages), $multicall);
		}
		elseif($receiver instanceof Player)
		{
			$player = $receiver;
			$find = false;
			$i = 0;
			while(!$find && $i < count($messages))
			{
				if($player->language == $messages[$i++]['Lang'])
				{
					$find = true;
				}
			}
			$message = $messages[$i - 1]['Text'];
			return $this->chatSendServerMessage($message, $player, $multicall);
		}
		elseif(is_array($receiver))
		{
			foreach($messages as $index =>$message)
			{
				$players = array();
				foreach($receiver as $key => $player)
				{
					if($player->language == $message['Lang'])
					{
						$players[] = $player;
						unset($receiver[$key]);
					}
					elseif($index == count($messages) - 1)
					{
						$players[] = $player;
						unset($receiver[$key]);
					}
				}
				if(count($players))
				$this->chatSendServerMessage($message['Text'], $players, $multicall);
			}
			return;
		}
		else 
		throw new InvalidArgumentException('receiver = '.print_r($receiver,true));
	}

	/**
	 * Send a text message without the server login to everyone if players is null.
	 * Players can be a Player object or an array of Player
	 * @param string $message
	 * @param Player|array[Player] $playerId
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatSendServerMessage($message, $players = null, $multicall = false)
	{
		if(!is_string($message))
		throw new InvalidArgumentException('message = '.print_r($message,true));
		$params = array($message);
		$method = 'ChatSendServerMessage';
		if(is_null($players))
		$params = array($message);
		elseif(is_array($players))
		{
			if(count($players))
			{
				$params[] = implode(',', Player::getPropertyFromArray($players, 'login'));
				$method .= 'ToLogin';
			}
		}
		elseif($players instanceof Player)
		{
			$params[] = $players->playerId;
			$method .= 'ToId';
		}
		else
		throw new InvalidArgumentException('players = '.print_r($players,true));

		return $this->execute($method, $params, $multicall);
	}

	/**
	 * Send a localised text message to all clients.
	 * The parameter is an array of structures {Lang='??', Text='...'}.
	 * If no matching language is found, the last text in the array is used.
	 * @param array $messages
	 * @param null|Player|array[Player] $receiver
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatSendToLanguage(array $messages, $receiver = null, $multicall = false)
	{
		if($receiver == null)
		{
			return $this->execute(ucfirst(__FUNCTION__), array($messages), $multicall);
		}
		elseif($receiver instanceof Player)
		{
			$player = $receiver;
			$find = false;
			$i = 0;
			while(!$find && $i < count($messages))
			{
				if($player->language == $messages[$i++]['Lang'])
				{
					$find = true;
				}
			}
			$message = $messages[$i - 1]['Text'];
			return $this->chatSend($message, $player, $multicall);
		}
		elseif(is_array($receiver))
		{
			foreach($messages as $index =>$message)
			{
				$players = array();
				foreach($receiver as $key => $player)
				{
					if($player->language == $message['Lang'])
					{
						$players[] = $player;
						unset($receiver[$key]);
					}
					elseif($index == count($messages) - 1)
					{
						$players[] = $player;
						unset($receiver[$key]);
					}
				}
				if(count($players))
				$this->chatSend($message['Text'], $players, $multicall);
			}
			return;
		}
		else 
		throw new InvalidArgumentException('receiver = '.print_r($receiver,true));
	}

		/**
	 * Send a text message to every Player or the a specified Player.
	 * If Player is null, the message will be delivered to every Player
	 * @param string $message
	 * @param Player|array[Player] $players
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatSend($message, $players, $multicall = false)
	{
		if(!is_string($message))
		throw new InvalidArgumentException('message = '.print_r($message,true));

		$params = array($message);
		$method = 'ChatSend';
		if(is_null($players))
		$params = array($message);
		elseif(is_array($players))
		{
			if(count($players))
			{
				$params[] = implode(',', Player::getPropertyFromArray($players, 'login'));
				$method .= 'ToLogin';
			}
		}
		elseif($players instanceof Player)
		{
			$params[] = $players->playerId;
			$method .= 'ToId';
		}
		else
		throw new InvalidArgumentException('players = '.print_r($players,true));

		return $this->execute($method, $params, $multicall);
	}

	/**
	 * Returns the last chat lines. Maximum of 40 lines.
	 * @return array
	 */
	function getChatLines()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * The chat messages are no longer dispatched to the players, they only go to the rpc callback
	 * and the controller has to manually forward them. The second (optional) parameter allows all
	 * messages from the server to be automatically forwarded.
	 * @param bool $enable
	 * @param bool $serverAutomaticForward
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatEnableManualRouting($enable, $serverAutomaticForward = false, $multicall = false)
	{
		if(!is_bool($enable))
		throw new InvalidArgumentException('enable = '.print_r($enable,true));
		if(!is_bool($serverAutomaticForward))
		throw new InvalidArgumentException('serverAutomaticForward = '.print_r($serverAutomaticForward,true));

		return $this->execute(ucfirst(__FUNCTION__), array($enable,$serverAutomaticForward), $multicall);
	}

	/**
	 * (Text, SenderLogin, DestLogin) Send a text message to the specified DestLogin (or everybody if empty)
	 * on behalf of SenderLogin. DestLogin can be a single login or a list of comma-separated logins.
	 * Only available if manual routing is enabled.
	 * @param string $message
	 * @param string $sender
	 * @param string $receiver
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatForwardToLogin($message,Player $sender,Player $receiver = null, $multicall = false)
	{
		if(!is_string($message))
		throw new InvalidArgumentException('message = '.print_r($message,true));

		$senderLogin = $sender->login;
		$receiverLogin = $receiver ? $receiver->login : '';
		
		return $this->execute(ucfirst(__FUNCTION__), array($message,$senderLogin,$receiverLogin), $multicall);
	}

	/**
	 * Display a notice on the client with the specified UId.
	 * The parameters are :
	 * the Uid of the client to whom the notice is sent,
	 * the text message to display,
	 * the UId of the avatar to display next to it (or '255' for no avatar),
	 * an optional 'max duration' in seconds (default: 3).
	 * @param string $message
	 * @param Player|array[Player] $receiver
	 * @param Player $player
	 * @param int $duration
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function sendNotice($receiver, $message, Player $player = null, $duration = 3, $multicall = false)
	{
		if(!is_string($message))
		throw new InvalidArgumentException('player = '.print_r($player,true));

		$params = array();
		$method = 'SendNotice';
		if(is_null($receiver))
		{
			$params[] = $message;
			if(is_null($player))
			$params[] = '';
			elseif($player instanceof Player)
			$params[] = $player->login;
		}
		elseif(is_array($receiver))
		{
			$params[] = implode(',', Player::getPropertyFromArray($receiver, 'login'));
			$params[] = $message;

			if(is_null($player))
			$params[] = '';
			elseif($player instanceof Player)
			$params[] = $player->login;

			$method .= 'ToLogin';
		}
		elseif($receiver instanceof Player)
		{
			$params[] = $receiver->playerId;
			$params[] = $message;

			if(is_null($player))
			$params[] = 255;
			elseif($player instanceof Player)
			$params[] = $player->playerId;

			$method .= 'ToId';
		}
		else
		throw new InvalidArgumentException('players = '.print_r($players,true));

		$params[] = $duration;
		return $this->execute($method, $params, $multicall);
	}

	/**
	 * Display a manialink page on the client of the specified Player(s).
	 * The first parameter is the login of the player,
	 * the other are identical to 'SendDisplayManialinkPage'.
	 * The players can be an object of player Type or an array of Player object
	 * @param null|Player|array[Player] $playerLogin
	 * @param string $manialink
	 * @param int $timeout
	 * @param bool $hideOnClick
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function sendDisplayManialinkPage($players, $manialink, $timeout, $hideOnClick, $multicall = false)
	{
		$method = 'SendDisplayManialinkPage';
		if(is_null($players))
		$params = array($manialink,$timeout,$hideOnClick);
		elseif(is_array($players))
		{
			$params = array(implode(',', Player::getPropertyFromArray($players, 'login')),$manialink,$timeout,$hideOnClick);
			$method .= 'ToLogin';
		}
		elseif($players instanceof Player)
		{
			$player = array($players->playerId,$manialink,$timeout,$hideOnClick);
			$method .= 'ToId';
		}
		else
		throw new InvalidArgumentException('players = '.print_r($players,true));

		if(!is_string($manialink))
		throw new InvalidArgumentException('manialink = '.print_r($manialink,true));
		if(!is_int($timeout))
		throw new InvalidArgumentException('timeout = '.print_r($timeout,true));
		if(!is_bool($hideOnClick))
		throw new InvalidArgumentException('hideOnClick = '.print_r($hideOnClick,true));

		return $this->execute($method, $params, $multicall);
	}

	/**
	 * Hide the displayed manialink page on the client with the specified login.
	 * Login can be a single login or a list of comma-separated logins.
	 * @param null|Player|array[Player] $players
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function sendHideManialinkPage($players = null, $multicall = false)
	{
		$method = 'SendHideManialinkPage';
		if(is_null($players))
		$params = array();
		elseif(is_array($players))
		{
			$params = array(implode(',', Player::getPropertyFromArray($players, 'login')));
			$method .= 'ToLogin';
		}
		elseif($players instanceof Player)
		{
			$player = array($players->playerId);
			$method .= 'ToId';
		}
		else
		throw new InvalidArgumentException('players = '.print_r($players,true));

		return $this->execute($method, array(), $multicall);
	}

	/**
	 * TODO struct stuff
	 * Returns the latest results from the current manialink page,
	 * as an array of structs {string Login, int PlayerId, int Result}
	 * Result==0 -> no answer, Result>0.... -> answer from the player.
	 * @return array
	 */
	function getManialinkPageAnswers()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Kick the player with the specified login, with an optional message.
	 * @param Player $playerLogin
	 * @param string $message
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function kick(Player $player, $message = '', $multicall = false)
	{
		if(!is_string($message))
		throw new InvalidArgumentException('message = '.print_r($message,true));

		return $this->execute('KickId', array($player->playerId,$message), $multicall);
	}

	/**
	 * Ban the player with the specified login, with an optional message.
	 * @param Player $player
	 * @param string $message
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function ban(Player $player, $message = '', $multicall = false)
	{
		if(!is_string($message))
		throw new InvalidArgumentException('message = '.print_r($message,true));

		return $this->execute('BanId', array($player->playerId,$message), $multicall);
	}

	/**
	 * Ban the player with the specified login, with a message.
	 * Add it to the black list, and optionally save the new list.
	 * @param Player $player
	 * @param string $message
	 * @param bool $saveList
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function banAndBlackList(Player $player, $message, $saveList = false, $multicall = false)
	{
		if(!is_string($message) || !$message)
		throw new InvalidArgumentException('message = '.print_r($message, true));
		if(!is_bool($saveList))
		throw new InvalidArgumentException('saveList = '.print_r($saveList,true));

		return $this->execute(ucfirst(__FUNCTION__), array($player->login, $message, $saveList), $multicall);
	}

	/**
	 * Unban the player with the specified client name.
	 * @param Player $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function unBan(Player $player, $multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($player->login), $multicall);
	}

	/**
	 * Clean the ban list of the server.
	 * @param bool $multicall
	 * @return bool
	 */
	function cleanBanList($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the list of banned players. This method takes two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the list. The list is an array of structures.
	 * Each structure contains the following fields : Login, ClientName and IPAddress.
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @return array[DedicatedApi\Structures\Player] The list is an array of object. Each object is an instance of DedicatedApi\Structures\Player
	 * @throws InvalidArgumentException
	 */
	function getBanList($length, $offset)
	{
		if(!is_int($length))
		throw new InvalidArgumentException('length = '.print_r($length,true));
		if(!is_int($offset))
		throw new InvalidArgumentException('offset = '.print_r($offset,true));

		$result = $this->execute(ucfirst(__FUNCTION__), array($length, $offset));
		return Structures\Player::fromArrayOfArray($result);
	}

	/**
	 * Blacklist the player with the specified login.
	 * @param Player $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function blackList(Player $player, $multicall = false)
	{
		return $this->execute('BlackListId', array($player->playerId), $multicall);
	}

	/**
	 * UnBlackList the player with the specified login.
	 * @param Player $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function unBlackList(Player $player, $multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($player->login), $multicall);
	}

	/**
	 * Clean the blacklist of the server.
	 * @param bool $multicall
	 * @return bool
	 */
	function cleanBlackList($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the list of blacklisted players.
	 * This method takes two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the list. The list is an array of structures.
	 * Each structure contains the following fields : Login.
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @return array[DedicatedApi\Structures\Player] The list is an array of structures. Each structure contains the following fields : Login.
	 * @throws InvalidArgumentException
	 */
	function getBlackList($length, $offset)
	{
		if(!is_int($length))
		throw new InvalidArgumentException('length = '.print_r($length,true));
		if(!is_int($offset))
		throw new InvalidArgumentException('offset = '.print_r($offset,true));

		$result = $this->execute(ucfirst(__FUNCTION__), array($length, $offset));
		return Structures\Player::fromArrayOfArray($result);
	}

	/**
	 * Load the black list file with the specified file name.
	 * @param string $filename blackList file name
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function loadBlackList($filename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Save the black list in the file with specified file name.
	 * @param string $filename blackList filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function saveBlackList($filename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Add the player with the specified login on the guest list.
	 * @param Player $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function addGuest(Player $player, $multicall = false)
	{
		return $this->execute('AddGuestId', array($player->playerId), $multicall);
	}

	/**
	 * Remove the player with the specified login from the guest list.
	 * @param Player $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function removeGuest(Player $player, $multicall = false)
	{
		return $this->execute('RemoveGuestId', array($player->playerId), $multicall);
	}

	/**
	 * Clean the guest list of the server.
	 * @param bool $multicall
	 * @return bool
	 */
	function cleanGuestList($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the list of players on the guest list.
	 * This method takes two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the list. The list is an array of structures.
	 * Each structure contains the following fields : Login.
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @return array[DedicatedApi\Structures\Player] The list is an array of structures. Each structure contains the following fields : Login.
	 * @throws InvalidArgumentException
	 */
	function getGuestList($length, $offset)
	{
		if(!is_int($length))
		throw new InvalidArgumentException('length = '.print_r($length,true));
		if(!is_int($offset))
		throw new InvalidArgumentException('offset = '.print_r($offset,true));

		$result = $this->execute(ucfirst(__FUNCTION__), array($length, $offset));
		return Structures\Player::fromArrayOfArray($result);
	}

	/**
	 *
	 * Load the guest list file with the specified file name.
	 * @param string $filename blackList file name
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function loadGuestList($filename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Save the guest list in the file with specified file name.
	 * @param string $filename blackList file name
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function saveGuestList($filename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Sets whether buddy notifications should be sent in the chat.
	 * login is the login of the player, or '' for global setting,
	 * enabled is the value.
	 * @param Player $player the object Player, or null for global setting
	 * @param bool $enable the value.
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setBuddyNotification(Player $player, $enable, $multicall = false)
	{
		if(!is_bool($enable))
		throw new InvalidArgumentException('enable = '.print_r($enable,true));

		if(is_null($player))
		$player = '';
		else
		$player = $player->login;

		return $this->execute(ucfirst(__FUNCTION__), array($player, $enable), $multicall);
	}

	/**
	 * Gets whether buddy notifications are enabled for login, or '' to get the global setting.
	 * @param $player $playerLogin the object Player, or null for global setting
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function getBuddyNotification(Player $player)
	{
		if(is_null($player))
		$params = array('');
		else
		$params = array($player->login);

		return $this->execute(ucfirst(__FUNCTION__), $params);
	}

	/**
	 * Write the data to the specified file. The filename is relative to the Tracks path
	 * @param string $filename The file to be written
	 * @param string $localFilename The file to be read to obtain the data
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function writeFile($filename, $localFilename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename, true));
		if(!file_exists($localFilename))
		throw new InvalidArgumentException('localFilename = '.print_r($localFilename,true));
		if(filesize($localFilename) > 512 * 1024 - 8)
		throw new InvalidArgumentException('file is too big');

		$inputStream = fopen($localFilename, 'r');
		$inputData = '' ;
		$streamSize = 0;

		while(!feof($inputStream))
		{
			$inputData .= fread($inputStream, 8192);
			$streamSize += 8192;
		}
		fclose($inputStream);

		$data = new Xmlrpc\Base64($inputData);

		return $this->execute(ucfirst(__FUNCTION__), array($filename, $data), $multicall);
	}

	/**
	 * Send the data to the specified player.
	 * Login can be a single login or a list of comma-separated logins.
	 * @param string $playerLogin Login can be a single login or a list of comma-separated logins.
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function tunnelSendData(Player $player, $filename, $multicall = false)
	{
		if(!file_exists($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));
		if(filesize($filename) > 4 * 1024)
		throw new InvalidArgumentException('file is too big');

		$inputStream = fopen($filename, 'r');
		$inputData = '' ;
		$streamSize = 0;

		while(!feof($inputStream))
		{
			$inputData .= fread($inputStream, 8192);
			$streamSize += 8192;
		}
		fclose($inputStream);

		$data = new Xmlrpc\Base64($inputData);

		return $this->execute('TunnelSendDataToId', array($player->playerId, $data), $multicall);
	}

	/**
	 * Just log the parameters and invoke a callback.
	 * Can be used to talk to other xmlrpc clients connected, or to make custom votes.
	 * If used in a callvote, the first parameter will be used as the vote message on the clients.
	 * @param string $message the message to log
	 * @param string $callback optionnal callback name
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function dedicatedEcho ($message, $callback = '', $multicall = false)
	{
		if(!is_string($message))
		throw new InvalidArgumentException('message = '.print_r($message, true));
		if(!is_string($callback))
		throw new InvalidArgumentException('callback = '.print_r($callback, true));

		return $this->execute('Echo', array($message, $callback), $multicall);
	}

	/**
	 * Ignore the specified Player.
	 * @param Player $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function ignore(Player $player, $multicall = false)
	{
		return $this->execute('IgnoreId', array($player->playerId), $multicall);
	}

	/**
	 * Unignore the player with the specified login.
	 * @param Player $playerLogin
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function unIgnore(Player $player, $multicall = false)
	{
		return $this->execute('UnIgnoreId', array($player->playerId), $multicall);
	}

	/**
	 * Clean the ignore list of the server.
	 * @param bool $multicall
	 * @return bool
	 */
	function cleanIgnoreList($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the list of ignored players. This method takes two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the list. The list is an array of structures.
	 * Each structure contains the following fields : Login.
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @return array[DedicatedApi\Structures\Player] The list is an array of structures. Each structure contains the following fields : Login.
	 * @throws InvalidArgumentException
	 */
	function getIgnoreList($length, $offset)
	{
		if(!is_int($length))
		throw new InvalidArgumentException('length = '.print_r($length,true));
		if(!is_int($offset))
		throw new InvalidArgumentException('offset = '.print_r($offset,true));

		$result = $this->execute(ucfirst(__FUNCTION__), array($length, $offset));
		return Structures\Player::fromArrayOfArray($result);
	}

	/**
	 * Pay coppers from the server account to a player, returns the BillId.
	 * This method takes three parameters:
	 * Login of the payee,
	 * Coppers to pay and
	 * Label to send with the payment.
	 * The creation of the transaction itself may cost coppers,
	 * so you need to have coppers on the server account.
	 * @param Player $player
	 * @param int $amount
	 * @param string $label
	 * @param bool $multicall
	 * @return int The Bill Id
	 * @throws InvalidArgumentException
	 */
	function pay($player, $amount, $label, $multicall = false)
	{
		if(!is_int($amount) || $amount < 1)
		throw new InvalidArgumentException('amount = '.print_r($amount, true));
		if(!is_string($label))
		throw new InvalidArgumentException('label = '.print_r($label, true));

		return $this->execute(ucfirst(__FUNCTION__), array($player->login, $amount, $label), $multicall);
	}

	/**
	 * Create a bill, send it to a player, and return the BillId.
	 * This method takes four parameters:
	 * LoginFrom of the payer,
	 * Coppers the player has to pay,
	 * Label of the transaction and
	 * optional LoginTo of the payee (if empty string, then the server account is used).
	 * The creation of the transaction itself may cost coppers,
	 * so you need to have coppers on the server account.
	 * @param Player $FromPlayer
	 * @param int $amount
	 * @param string $label
	 * @param string $playerLoginTo
	 * @param bool $multicall
	 * @return int
	 * @throws InvalidArgumentException
	 */
	function sendBill(Player $fromPlayer, $amount, $label, Player $toPlayer = null, $multicall = false)
	{
		if(!is_int($amount) || $amount < 1)
		throw new InvalidArgumentException('amount = '.print_r($amount, true));
		if(!is_string($label))
		throw new InvalidArgumentException('label = '.print_r($label, true));

		if(is_null($toPlayer))
		$toPlayer = '';
		elseif($toPlayer instanceof Player)
		$toPlayer = $toPlayer->login;

		return $this->execute(ucfirst(__FUNCTION__), array($fromPlayer, $amount, $label, $toPlayer), $multicall);
	}

	/**
	 * Returns the current state of a bill.
	 * This method takes one parameter, the BillId.
	 * Returns a struct containing
	 * State, StateName and TransactionId.
	 * Possible enum values are: CreatingTransaction, Issued, ValidatingPayement, Payed, Refused, Error.
	 * @param int $billId
	 * @return Bill
	 * @throws InvalidArgumentException
	 */
	function getBillState($billId)
	{
		if(!is_int($billId))
		throw new InvalidArgumentException('billId = '.print_r($billId, true));

		$result = $this->execute(ucfirst(__FUNCTION__), array($billId));
		return Structures\Bill::fromArray($result);
	}

	/**
	 * Returns the current number of coppers on the server account.
	 * @return int
	 */
	function getServerCoppers()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Get some system infos.
	 * Return a struct containing:
	 * PublishedIp, Port, P2PPort, ServerLogin, ServerPlayerId
	 * @return ManiaLive\DedicatedApi\Structures\SystemInfos
	 */
	function getSystemInfo()
	{
		$result = $this->execute(ucfirst(__FUNCTION__));
		return Structures\SystemInfos::fromArray($result);
	}

	/**
	 * Set a new server name in utf8 format.
	 * @param string $serverName
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerName($serverName, $multicall = false)
	{
		if(!is_string($serverName))
		throw new InvalidArgumentException('serverName = '.print_r($serverName,true));

		return $this->execute(ucfirst(__FUNCTION__), array($serverName), $multicall);
	}

	/**
	 * Get the server name in utf8 format.
	 * @return string
	 */
	function getServerName()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new server comment in utf8 format.
	 * @param string $serverComment
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerComment($serverComment, $multicall = false)
	{
		if(!is_string($serverComment))
		throw new InvalidArgumentException('serverComment = '.print_r($serverComment,true));

		return $this->execute(ucfirst(__FUNCTION__), array($serverComment), $multicall);
	}

	/**
	 * Get the server comment in utf8 format.
	 * @return string
	 */
	function getServerComment()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether the server should be hidden from the public server list
	 * (0 = visible, 1 = always hidden, 2 = hidden from nations).
	 * @param int $visibility
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setHideServer($visibility, $multicall = false)
	{
		if($visibility !== 0 && $visibility !== 1 && $visibility !== 2)
		throw new InvalidArgumentException('visibility = '.print_r($visibility,true));

		return $this->execute(ucfirst(__FUNCTION__), array($visibility), $multicall);
	}

	/**
	 * Get whether the server wants to be hidden from the public server list.
	 * @return string
	 */
	function getHideServer()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns true if this is a relay server.
	 * @return bool
	 */
	function isRelayServer()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new password for the server.
	 * @param string $serverPassword
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerPassword($serverPassword, $multicall = false)
	{
		if(!is_string($serverPassword))
		throw new DedicatedApiInvalidArgumentExcepption('serverPassword = '.print_r($serverPassword,true));

		return $this->execute(ucfirst(__FUNCTION__), array($serverPassword), $multicall);
	}

	/**
	 * Get the server password if called as Admin or Super Admin, else returns if a password is needed or not.
	 * Get the server name in utf8 format.
	 * @return bool|string
	 */
	function getServerPassword()
	{
		$params = array();
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new password for the spectator mode.
	 * @param string $serverPassword
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerPasswordForSpectator($serverPassword, $multicall = false)
	{
		if(!is_string($serverPassword))
		throw new DedicatedApiInvalidArgumentExcepption('serverPassword = '.print_r($serverPassword,true));

		return $this->execute(ucfirst(__FUNCTION__), array($serverPassword), $multicall);
	}

	/**
	 * Get the password for spectator mode if called as Admin or Super Admin, else returns if a password is needed or not.
	 * @return bool|string
	 */
	function getServerPasswordForSpectator()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new maximum number of players.
	 * Requires a challenge restart to be taken into account.
	 * @param int $maxPlayers
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setMaxPlayers($maxPlayers, $multicall = false)
	{
		if(!is_int($maxPlayers))
		throw new InvalidArgumentException('maxPlayers = '.print_r($maxPlayers, true));

		return $this->execute(ucfirst(__FUNCTION__), array($maxPlayers), $multicall);
	}

	/**
	 * Get the current and next maximum number of players allowed on server.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @param bool $multicall
	 * @return array
	 * @throws InvalidArgumentException
	 */
	function getMaxPlayers()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new maximum number of spectators.
	 * Requires a challenge restart to be taken into account.
	 * @param int $maxSpectators
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setMaxSpectators($maxSpectators, $multicall = false)
	{
		if(!is_int($maxSpectators))
		throw new InvalidArgumentException('maxPlayers = '.print_r($maxSpectators, true));

		return $this->execute(ucfirst(__FUNCTION__), array($maxSpectators), $multicall);
	}



	/**
	 * Get the current and next maximum number of spectators allowed on server.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getMaxSpectators()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Enable or disable peer-to-peer upload from server.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function enableP2PUpload($enable, $multicall = false)
	{
		if(!is_bool($enable))
		throw new InvalidArgumentException('enable = '.print_r($enable, true));

		return $this->execute(ucfirst(__FUNCTION__),array($enable),$multicall);
	}

	/**
	 * Returns if the peer-to-peer upload from server is enabled.
	 * @return bool
	 */
	function isP2PUpload()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Enable or disable peer-to-peer download from server.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function enableP2PDownload($enable, $multicall = false)
	{
		if(!is_bool($enable))
		throw new InvalidArgumentException('enable = '.print_r($enable, true));

		return $this->execute(ucfirst(__FUNCTION__),array($enable),$multicall);
	}

	/**
	 * Returns if the peer-to-peer download from server is enabled.
	 * @return bool
	 */
	function isP2PDownload()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Sets up- and download speed for the server in kbps.
	 * @param integer $rates
	 * @param bool $multicall
	 * @return bool
	 */
	function setConnectionRates($rates, $multicall = false)
	{
		if (!is_int($rates))
			throw new InvalidArgumentException('rates = '.print_r($rates, true));
		
		return $this->execute(ucfirst(__FUNCTION__),array($rates),$multicall);
	}
	
	/**
	 * Allow clients to download challenges from the server.
	 * @param bool $allow
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function allowChallengeDownload($allow, $multicall = false)
	{
		if(!is_bool($allow))
		throw new InvalidArgumentException('allow = '.print_r($allow, true));

		return $this->execute(ucfirst(__FUNCTION__),array($allow),$multicall);
	}

	/**
	 * Returns if clients can download challenges from the server.
	 * @return bool
	 */
	function isChallengeDownloadAllowed()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Enable the autosaving of all replays (vizualisable replays with all players,
	 * but not validable) on the server.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function autoSaveReplays($enable, $multicall = false)
	{
		if(!is_bool($enable))
		throw new InvalidArgumentException('enable = '.print_r($enable, true));

		return $this->execute(ucfirst(__FUNCTION__), array($enable),$multicall);
	}

	/**
	 * Returns if autosaving of all replays is enabled on the server.
	 * @return bool
	 */
	function isAutoSaveReplaysEnabled()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Enable the autosaving on the server of validation replays, every time a player makes a new time.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function autoSaveValidationReplays($enable, $multicall = false)
	{
		if(!is_bool($enable))
		throw new InvalidArgumentException('enable = '.print_r($enable, true));

		return $this->execute(ucfirst(__FUNCTION__),array($enable),$multicall);
	}

	/**
	 * Returns if autosaving of validation replays is enabled on the server.
	 * @return bool
	 */
	function isAutoSaveValidationReplaysEnabled()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Saves the current replay (vizualisable replays with all players, but not validable).
	 * Pass a filename, or '' for an automatic filename.
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function saveCurrentReplay($filename = '', $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.$print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__),array($filename),$multicall);
	}

	/**
	 * Saves a replay with the ghost of all the players' best race.
	 * First parameter is the player object(or null for all players),
	 * Second parameter is the filename, or '' for an automatic filename.
	 * @param Player $player is the player object(or null for all players)
	 * @param string $filename is the filename, or '' for an automatic filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function saveBestGhostsReplay(Player $player, $filename = '', $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.$print_r($filename, true));

		$playerLogin = (is_null($player) ? '' : $player->login);

		return $this->execute(ucfirst(__FUNCTION__),array($playerLogin,$filename),$multicall);
	}

	/**
	 * Returns a replay containing the data needed to validate the current best time of the player.
	 * The parameter is the login of the player.
	 * @param Player $player
	 * @return string base64 encoded
	 * @throws InvalidArgumentException
	 */
	function getValidationReplay(Player $player)
	{
		return $this->execute(ucfirst(__FUNCTION__),array($player->login,$filename));
	}

	/**
	 * Set a new ladder mode between ladder disabled (0) and forced (1).
	 * Requires a challenge restart to be taken into account.
	 * @param int $mode
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setLadderMode($mode, $multicall = false)
	{
		if($mode !== 0 && $mode !== 1)
		throw new InvalidArgumentException('mode = '.print_r($mode, true));

		return $this->execute(ucfirst(__FUNCTION__),array($mode),$multicall);
	}

	/**
	 * Get the current and next ladder mode on server.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getLadderMode()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Get the ladder points limit for the players allowed on this server.
	 * The struct returned contains two fields LadderServerLimitMin and LadderServerLimitMax.
	 * @return array
	 */
	function getLadderServerLimits()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the network vehicle quality to Fast (0) or High (1).
	 * Requires a challenge restart to be taken into account.
	 * @param int $quality
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setVehicleNetQuality($quality, $multicall = false)
	{
		if($quality !== 0 && $quality !== 1)
		throw new InvalidArgumentException('quality = '.print_r($quality, true));

		return $this->execute(ucfirst(__FUNCTION__),array($quality),$multicall);
	}

	/**
	 * Get the current and next network vehicle quality on server.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getVehicleNetQuality($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set new server options using the struct passed as parameters.
	 * This struct must contain the following fields :
	 * Name, Comment, Password, PasswordForSpectator, NextMaxPlayers,
	 * NextMaxSpectators, IsP2PUpload, IsP2PDownload, NextLadderMode,
	 * NextVehicleNetQuality, NextCallVoteTimeOut, CallVoteRatio,
	 * AllowChallengeDownload, AutoSaveReplays,
	 *
	 * optionally for forever:
	 * RefereePassword, RefereeMode, AutoSaveValidationReplays,
	 * HideServer, UseChangingValidationSeed.
	 *
	 * A change of :
	 * NextMaxPlayers, NextMaxSpectators, NextLadderMode, NextVehicleNetQuality,
	 * NextCallVoteTimeOut or UseChangingValidationSeed
	 * requires a challenge restart to be taken into account.
	 * @param array $options
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerOptions(array $options, $multicall = false)
	{
		if(
		!is_array($options) || !array_key_exists('Name', $options) || !array_key_exists('Comment', $options)
		|| !array_key_exists('Password', $options) || !array_key_exists('PasswordForSpectator', $options)
		|| !array_key_exists('NextMaxPlayers', $options) || !array_key_exists('NextMaxSpectators', $options)
		|| !array_key_exists('IsP2PUpload', $options) || !array_key_exists('IsP2PDownload', $options)
		|| !array_key_exists('NextLadderMode', $options) || !array_key_exists('NextVehicleNetQuality', $options)
		|| !array_key_exists('NextCallVoteTimeOut', $options) || !array_key_exists('CallVoteRatio', $options)
		|| !array_key_exists('AllowChallengeDownload', $options) || !array_key_exists('AutoSaveReplays', $options)
		)
		throw  new InvalidArgumentException('options = '.print_r($options,true));

		return $this->execute(ucfirst(__FUNCTION__), array($options), $multicall);
	}

	/**
	 * Optional parameter for compatibility: struct version (0 = united, 1 = forever).
	 * Returns a struct containing the server options:
	 * Name, Comment, Password, PasswordForSpectator, CurrentMaxPlayers, NextMaxPlayers,
	 * CurrentMaxSpectators, NextMaxSpectators, IsP2PUpload, IsP2PDownload, CurrentLadderMode,
	 * NextLadderMode, CurrentVehicleNetQuality, NextVehicleNetQuality, CurrentCallVoteTimeOut,
	 * NextCallVoteTimeOut, CallVoteRatio, AllowChallengeDownload and AutoSaveReplays,
	 *
	 * and additionally for forever:
	 * RefereePassword, RefereeMode, AutoSaveValidationReplays, HideServer,
	 * CurrentUseChangingValidationSeed, NextUseChangingValidationSeed.
	 * @param int $compability
	 * @return \ManiaHome\DedicatedApi\Structures\ServerOptions
	 * @throws InvalidArgumentException
	 */
	function getServerOptions($compatibility = 1)
	{
		if($compatibility !== 0 && $compatibility !== 1)
		throw  new InvalidArgumentException('compatibility = '.print_r($compatibility,true));

		return ServerOptions::fromArray($this->execute(ucfirst(__FUNCTION__), array($compatibility)));
	}

	/**
	 * Defines the packmask of the server. Can be 'United', 'Nations', 'Sunrise', 'Original',
	 * or any of the environment names. (Only challenges matching the packmask will be
	 * allowed on the server, so that player connecting to it know what to expect.)
	 * Only available when the server is stopped.
	 * @param string $packMask
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerPackMask($packMask, $multicall = false)
	{
		if(!is_string($packMask))
		throw new InvalidArgumentException('packMask = '.print_r($packMask, true));

		return $this->execute(ucfirst(__FUNCTION__), array($packMask), $multicall);
	}

	/**
	 * Get the packmask of the server.
	 * @return string
	 */
	function getServerPackMask()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the mods to apply on the clients. Parameters:
	 * Override, if true even the challenges with a mod will be overridden by the server setting;
	 * Mods, an array of structures [{EnvName, Url}, ...].
	 * Requires a challenge restart to be taken into account.
	 * @param bool $override
	 * @param array $mods
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setForcedMods($override, $mods, $multicall = false)
	{
		if(!is_bool($override))
		throw new InvalidArgumentException('override = '.print_r($override, true));
		if(is_array($mods))
		{
			$modList = array();
			foreach ($mods as $mod)
			{
				if(!($mod instanceof Structures\Mod))
				throw new InvalidArgumentException('mods = '.print_r($mods, true));
				else 
				$modList[] = $mods->toArray();
			}
		}
		elseif($mods instanceof Structures\Mod)
		$modList = array($mods->toArray());
		else
		throw new InvalidArgumentException('mods = '.print_r($mods, true));

		return $this->execute(ucfirst(__FUNCTION__), array($override, $modList), $multicall);
	}

	/**
	 * Get the mods settings.
	 * @return array the first value is a boolean which indicate if the mods override existing mods, the second is an array of objet of Mod type
	 */
	function getForcedMods()
	{
		$result = $this->execute(ucfirst(__FUNCTION__));
		$result['Mods'] = Structures\Mod::fromArrayOfArray($result['Mods']);
		return $result;
	}

	/**
	 * Set the music to play on the clients. Parameters:
	 * Override, if true even the challenges with a custom music will be overridden by the server setting,
	 * UrlOrFileName for the music.
	 * Requires a challenge restart to be taken into account
	 * @param bool $override
	 * @param string $music
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 */
	function setForcedMusic($override, $music, $multicall = false)
	{
		if(!is_bool($override))
		throw new InvalidArgumentException('override = '.print_r($override, true));
		if(!is_string($music))
		throw new InvalidArgumentException('music = '.print_r($music, true));

		return $this->execute(ucfirst(__FUNCTION__), array($override, $music), $multicall);
	}

	/**
	 * Get the music setting.
	 * @return Music
	 */
	function getForcedMusic()
	{
		return Structures\Music::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Defines a list of remappings for player skins. It expects a list of structs Orig, Name, Checksum, Url.
	 * Orig is the name of the skin to remap, or '*' for any other. Name, Checksum, Url define the skin to use.
	 * (They are optional, you may set value '' for any of those. All 3 null means same as Orig).
	 * Will only affect players connecting after the value is set.
	 * @param array[ManiaLive\DedicatedApi\Structures\Skin] $skins
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setForcedSkins(array $skins, $multicall = false)
	{
		if(!is_array($skins))
		throw new InvalidArgumentException('skins = '.print_r($skins, true));

		$skinParameter = array();
		foreach ($skins as $key => $skin)
		{
			if($skin instanceof namespace\Structures\Skin)
			{
				$skinParameter[$key] = array();
				$skinParameter[$key]['Orig'] 		= $skin->Orig;
				$skinParameter[$key]['Name'] 		= $skin->Name;
				$skinParameter[$key]['Checksum'] 	= $skin->Checksum;
				$skinParameter[$key]['Url'] 		= $skin->Url;
			}
			elseif(!is_array($skin) || !array_key_exists('Orig', $skin) && !array_key_exists('Name', $skin) && !array_key_exists('Checksum', $skin) && !array_key_exists('Url', $skin))
			{
				throw new InvalidArgumentException('skins['.$key.'] = '.print_r($skins[$key], true));
			}
			else
			{
				$skinParameter[$key] = $skin;
			}
		}

		return $this->execute(ucfirst(__FUNCTION__), array($skinParameter), $multicall);
	}

	/**
	 * Get the current forced skins.
	 * @return array[\ManiaLive\DedicatedApi\Structures\Skin]
	 */
	function getForcedSkins()
	{
		return Structures\Skin::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns the last error message for an internet connection.
	 * @return string
	 */
	function getLastConnectionErrorMessage()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new password for the referee mode.
	 * @param string $refereePassword
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setRefereePassword($refereePassword, $multicall = false)
	{
		if(!is_string($refereePassword))
		throw new DedicatedApiInvalidArgumentExcepption('refereePassword = '.print_r($refereePassword,true));

		return $this->execute(ucfirst(__FUNCTION__), array($refereePassword), $multicall);
	}

	/**
	 * Get the password for referee mode if called as Admin or Super Admin,
	 * else returns if a password is needed or not.
	 * @return bool|string
	 */
	function getRefereePassword()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether the game should use a variable validation seed or not.
	 * Requires a challenge restart to be taken into account.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setUseChangingValidationSeed($enable, $multicall = false)
	{
		if(!is_bool($enable))
		throw new InvalidArgumentException('enable = '.print_r($enable, true));

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Get the current and next value of UseChangingValidationSeed.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getUseChangingValidationSeed()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Sets whether the server is in warm-up phase or not.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setWarmUp($enable, $multicall = false)
	{
		if(!is_bool($enable))
		throw new InvalidArgumentException('enable = '.print_r($enable, true));

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Returns whether the server is in warm-up phase.
	 * @return bool
	 */
	function getWarmUp()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Restarts the challenge, with an optional boolean parameter DontClearCupScores (only available in cup mode).
	 * @param bool $dontClearCupScores
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function restartChallenge($dontClearCupScores = false, $multicall = false)
	{
		if(!is_bool($dontClearCupScores))
		throw new InvalidArgumentException('dontClearCupScores = '.print_r($dontClearCupScores, true));

		return $this->execute(ucfirst(__FUNCTION__), array($dontClearCupScores), $multicall);
	}

	/**
	 * Switch to next challenge, with an optional boolean parameter DontClearCupScores (only available in cup mode).
	 * @param bool $dontClearCupScores
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function nextChallenge($dontClearCupScores = false, $multicall = false)
	{
		if(!is_bool($dontClearCupScores))
		throw new InvalidArgumentException('dontClearCupScores = '.print_r($dontClearCupScores, true));

		return $this->execute(ucfirst(__FUNCTION__), array($dontClearCupScores), $multicall);
	}

	/**
	 * Stop the server.
	 * @param bool $multicall
	 * @return bool
	 */
	function stopServer($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * In Rounds or Laps mode, force the end of round without waiting for all players to giveup/finish.
	 * @param bool $multicall
	 * @return bool
	 */
	function forceEndRound($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Set new game settings using the struct passed as parameters.
	 * This struct must contain the following fields :
	 * GameMode, ChatTime, RoundsPointsLimit, RoundsUseNewRules, RoundsForcedLaps, TimeAttackLimit,
	 * TimeAttackSynchStartPeriod, TeamPointsLimit, TeamMaxPoints, TeamUseNewRules, LapsNbLaps, LapsTimeLimit,
	 * FinishTimeout, and optionally: AllWarmUpDuration, DisableRespawn, ForceShowAllOpponents, RoundsPointsLimitNewRules,
	 * TeamPointsLimitNewRules, CupPointsLimit, CupRoundsPerChallenge, CupNbWinners, CupWarmUpDuration.
	 * Requires a challenge restart to be taken into account.
	 * @param array $gameInfos
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setGameInfos(array $gameInfos, $multicall = false)
	{
		if(!is_array($gameInfos)
		|| !array_key_exists('GameMode', $gameInfos)
		|| !array_key_exists('ChatTime', $gameInfos)
		|| !array_key_exists('RoundsPointsLimit', $gameInfos)
		|| !array_key_exists('RoundsUseNewRules', $gameInfos)
		|| !array_key_exists('RoundsForcedLaps', $gameInfos)
		|| !array_key_exists('TimeAttackLimit', $gameInfos)
		|| !array_key_exists('TimeAttackSynchStartPeriod', $gameInfos)
		|| !array_key_exists('TeamPointsLimit', $gameInfos)
		|| !array_key_exists('TeamMaxPoints', $gameInfos)
		|| !array_key_exists('TeamUseNewRules', $gameInfos)
		|| !array_key_exists('LapsNbLaps', $gameInfos)
		|| !array_key_exists('LapsTimeLimit', $gameInfos)
		|| !array_key_exists('FinishTimeout', $gameInfos)
		)
		throw new InvalidArgumentException('gameInfos = '.print_r($gameInfos,true));

		return $this->execute(ucfirst(__FUNCTION__), array($gameInfos), $multicall);
	}

	/**
	 * Optional parameter for compatibility:
	 * struct version (0 = united, 1 = forever).
	 * Returns a struct containing the current game settings, ie:
	 * GameMode, ChatTime, NbChallenge, RoundsPointsLimit, RoundsUseNewRules, RoundsForcedLaps,
	 * TimeAttackLimit, TimeAttackSynchStartPeriod, TeamPointsLimit, TeamMaxPoints, TeamUseNewRules,
	 * LapsNbLaps, LapsTimeLimit, FinishTimeout,
	 * additionally for version 1: AllWarmUpDuration, DisableRespawn, ForceShowAllOpponents, RoundsPointsLimitNewRules,
	 * TeamPointsLimitNewRules, CupPointsLimit, CupRoundsPerChallenge, CupNbWinners, CupWarmUpDuration.
	 * @param int $compatibility
	 * @return GameInfos
	 * @throws InvalidArgumentException
	 */
	function getCurrentGameInfo($compatibility = 1)
	{
		if($compatibility !== 1 && $compatibility != 0)
		throw new InvalidArgumentException('compatibility = '.print_r($compatibility,true));

		return Structures\GameInfos::fromArray($this->execute(ucfirst(__FUNCTION__), array($compatibility)));
	}

	/**
	 * Optional parameter for compatibility:
	 * struct version (0 = united, 1 = forever).
	 * Returns a struct containing the game settings for the next challenge, ie:
	 * GameMode, ChatTime, NbChallenge, RoundsPointsLimit, RoundsUseNewRules, RoundsForcedLaps,
	 * TimeAttackLimit, TimeAttackSynchStartPeriod, TeamPointsLimit, TeamMaxPoints, TeamUseNewRules,
	 * LapsNbLaps, LapsTimeLimit, FinishTimeout,
	 * additionally for version 1: AllWarmUpDuration, DisableRespawn, ForceShowAllOpponents, RoundsPointsLimitNewRules,
	 * TeamPointsLimitNewRules, CupPointsLimit, CupRoundsPerChallenge, CupNbWinners, CupWarmUpDuration.
	 * @param int $compatibility
	 * @return GameInfos
	 * @throws InvalidArgumentException
	 */
	function getNextGameInfo($compatibility = 1)
	{
		if($compatibility !== 1 && $compatibility != 0)
		throw new InvalidArgumentException('compatibility = '.print_r($compatibility,true));

		return Structures\GameInfos::fromArray($this->execute(ucfirst(__FUNCTION__),array($compatibility)));
	}

	/**
	 * Optional parameter for compatibility: struct version (0 = united, 1 = forever).
	 * Returns a struct containing two other structures,
	 * the first containing the current game settings and the second the game settings for next challenge.
	 * The first structure is named CurrentGameInfos and the second NextGameInfos.
	 * @param int $compatibility
	 * @return array[GameInfos]
	 * @throws InvalidArgumentException
	 */
	function getGameInfos($compatibility = 1)
	{
		if($compatibility !== 1 && $compatibility != 0)
		throw new InvalidArgumentException('compatibility = '.print_r($compatibility,true));

		return Structures\GameInfos::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__),array($compatibility)));
	}

	/**
	 * Set a new game mode between Rounds (0), TimeAttack (1), Team (2), Laps (3), Stunts (4) and Cup (5).
	 * Requires a challenge restart to be taken into account.
	 * @param int $gameMode
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setGameMode($gameMode, $multicall = false)
	{
		if(!is_int($gameMode) && ($gameMode < 0  || $gameMode > 5))
		throw new InvalidArgumentException('gameMode = '.print_r($gameMode,true));

		return $this->execute(ucfirst(__FUNCTION__), array($gameMode), $multicall);
	}

	/**
	 * Get the current game mode.
	 * @return int
	 */
	function getGameMode()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new chat time value in milliseconds (actually 'chat time' is the duration of the end race podium, 0 means no podium displayed.).
	 * @param int $chatTime
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setChatTime($chatTime, $multicall = false)
	{
		if(!is_int($chatTime))
		throw new InvalidArgumentException('chatTime = '.print_r($chatTime,true));

		return $this->execute(ucfirst(__FUNCTION__), array($chatTime), $multicall);
	}

	/**
	 * Get the current and next chat time. The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getChatTime($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new finish timeout (for rounds/laps mode) value in milliseconds.
	 * 0 means default. 1 means adaptative to the duration of the challenge.
	 * Requires a challenge restart to be taken into account.
	 * @param int $finishTimeout
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setFinishTimeout($finishTimeout, $multicall = false)
	{
		if(!is_int($finishTimeout))
		throw new InvalidArgumentException('chatTime = '.print_r($finishTimeout,true));

		return $this->execute(ucfirst(__FUNCTION__), array($finishTimeout), $multicall);
	}

	/**
	 * Get the current and next FinishTimeout. The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getFinishTimeout()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether to enable the automatic warm-up phase in all modes.
	 * 0 = no, otherwise it's the duration of the phase, expressed in number of rounds (in rounds/team mode),
	 * or in number of times the gold medal time (other modes).
	 * Requires a challenge restart to be taken into account.
	 * @param int $warmUpDuration
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setAllWarmUpDuration($warmUpDuration, $multicall = false)
	{
		if(!is_int($warmUpDuration))
		throw new InvalidArgumentException('warmUpDuration = '.print_r($warmUpDuration,true));

		return $this->execute(ucfirst(__FUNCTION__), array($warmUpDuration), $multicall);
	}

	/**
	 * Get whether the automatic warm-up phase is enabled in all modes. The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getAllWarmUpDuration()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether to disallow players to respawn.
	 * Requires a challenge restart to be taken into account.
	 * @param bool $disableRespawn
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setDisableRespawn($disableRespawn, $multicall = false)
	{
		if(!is_bool($disableRespawn))
		throw new InvalidArgumentException('disableRespawn = '.print_r($disableRespawn,true));

		return $this->execute(ucfirst(__FUNCTION__), array($disableRespawn), $multicall);
	}

	/**
	 * Get whether players are disallowed to respawn. The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getDisableRespawn()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether to override the players preferences and always display all opponents
	 * 0=no override, 1=show all, other value=minimum number of opponents.
	 * Requires a challenge restart to be taken into account.
	 * @param int $forceShowAllOpponents
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setForceShowAllOpponents($forceShowAllOpponents, $multicall = false)
	{
		if(!is_int($forceShowAllOpponents))
		throw new InvalidArgumentException('forceShowAllOpponents = '.print_r($forceShowAllOpponents,true));

		return $this->execute(ucfirst(__FUNCTION__), array($forceShowAllOpponents), $multicall);
	}

	/**
	 * Get whether players are forced to show all opponents. The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getForceShowAllOpponents()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new time limit for time attack mode.
	 * Requires a challenge restart to be taken into account.
	 * @param int $timeAttackLimit
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setTimeAttackLimit($timeAttackLimit, $multicall = false)
	{
		if(!is_int($timeAttackLimit))
		throw new InvalidArgumentException('timeAttackLimit = '.print_r($timeAttackLimit,true));

		return $this->execute(ucfirst(__FUNCTION__), array($timeAttackLimit), $multicall);
	}

	/**
	 * Get the current and next time limit for time attack mode. The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getTimeAttackLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new synchronized start period for time attack mode.
	 * Requires a challenge restart to be taken into account.
	 * @param int $timeAttackSynchPeriod
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setTimeAttackSynchStartPeriod($timeAttackSynchPeriod, $multicall = false)
	{
		if(!is_int($timeAttackSynchPeriod))
		throw new InvalidArgumentException('timeAttackSynchPeriod = '.print_r($timeAttackSynchPeriod,true));

		return $this->execute(ucfirst(__FUNCTION__), array($timeAttackSynchPeriod), $multicall);
	}

	/**
	 * Get the current and synchronized start period for time attack mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getTimeAttackSynchStartPeriod()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new time limit for laps mode.
	 * Requires a challenge restart to be taken into account.
	 * @param int $lapsTimeLimit
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setLapsTimeLimit($lapsTimeLimit, $multicall = false)
	{
		if(!is_int($lapsTimeLimit))
		throw new InvalidArgumentException('lapsTimeLimit = '.print_r($lapsTimeLimit,true));

		return $this->execute(ucfirst(__FUNCTION__), array($lapsTimeLimit), $multicall);
	}

	/**
	 * Get the current and next time limit for laps mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getLapsTimeLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new number of laps for laps mode.
	 * Requires a challenge restart to be taken into account.
	 * @param int $nbLaps
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setNbLaps($nbLaps, $multicall = false)
	{
		if(!is_int($nbLaps))
		throw new InvalidArgumentException('nbLaps = '.print_r($nbLaps,true));

		return $this->execute(ucfirst(__FUNCTION__), array($nbLaps), $multicall);
	}

	/**
	 * Get the current and next number of laps for laps mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getNbLaps()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new number of laps for rounds mode
	 * 0 = default, use the number of laps from the challenges,
	 * otherwise forces the number of rounds for multilaps challenges.
	 * Requires a challenge restart to be taken into account.
	 * @param int $roundForcedLaps
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setRoundForcedLaps($roundForcedLaps, $multicall = false)
	{
		if(!is_int($roundForcedLaps))
		throw new InvalidArgumentException('roundForcedLaps = '.print_r($roundForcedLaps,true));

		return $this->execute(ucfirst(__FUNCTION__), array($roundForcedLaps), $multicall);
	}

	/**
	 * Get the current and next number of laps for rounds mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getRoundForcedLaps()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new points limit for rounds mode (value set depends on UseNewRulesRound).
	 * Requires a challenge restart to be taken into account.
	 * @param int $roundPointsLimit
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setRoundPointsLimit($roundPointsLimit, $multicall = false)
	{
		if(!is_int($roundPointsLimit))
		throw new InvalidArgumentException('roundPointsLimit = '.print_r($roundPointsLimit,true));

		return $this->execute(ucfirst(__FUNCTION__), array($roundPointsLimit), $multicall);
	}

	/**
	 * Get the current and next points limit for rounds mode (values returned depend on UseNewRulesRound).
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getRoundPointsLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the points used for the scores in rounds mode.
	 * Points is an array of decreasing integers for the players from the first to last.
	 * And you can add an optional boolean to relax the constraint checking on the scores.
	 * @param array $roundCustomPoints
	 * @param bool $relaxChecking
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setRoundCustomPoints(array $roundCustomPoints, $relaxChecking = false, $multicall = false)
	{
		if(!is_array($roundCustomPoints))
		throw new InvalidArgumentException('roundCustomPoints = '.print_r($roundCustomPoints,true));
		if(!is_bool($relaxChecking))
		throw new InvalidArgumentException('relaxChecking = '.print_r($relaxChecking,true));

		return $this->execute(ucfirst(__FUNCTION__), array($roundCustomPoints, $relaxChecking), $multicall);
	}

	/**
	 * Gets the points used for the scores in rounds mode.
	 * @param bool $multicall
	 * @return array
	 */
	function getRoundCustomPoints($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the points used for the scores in rounds mode.
	 * Points is an array of decreasing integers for the players from the first to last.
	 * And you can add an optional boolean to relax the constraint checking on the scores.
	 * @param bool $useNewRulesRound
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setUseNewRulesRound($useNewRulesRound, $multicall = false)
	{
		if(!is_bool($useNewRulesRound))
		throw new InvalidArgumentException('useNewRulesRound = '.print_r($useNewRulesRound,true));

		return $this->execute(ucfirst(__FUNCTION__), array($useNewRulesRound), $multicall);
	}

	/**
	 * Gets the points used for the scores in rounds mode.
	 * @return array
	 */
	function getUseNewRulesRound()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new points limit for team mode (value set depends on UseNewRulesTeam).
	 * Requires a challenge restart to be taken into account.
	 * @param int $teamPointsLimit
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setTeamPointsLimit($teamPointsLimit, $multicall = false)
	{
		if(!is_int($teamPointsLimit))
		throw new InvalidArgumentException('teamPointsLimit = '.print_r($teamPointsLimit,true));

		return $this->execute(ucfirst(__FUNCTION__), array($teamPointsLimit), $multicall);
	}

	/**
	 * Get the current and next points limit for team mode (values returned depend on UseNewRulesTeam).
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getTeamPointsLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new number of maximum points per round for team mode.
	 * Requires a challenge restart to be taken into account.
	 * @param int $maxPointsTeam
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setMaxPointsTeam($maxPointsTeam, $multicall = false)
	{
		if(!is_int($maxPointsTeam))
		throw new InvalidArgumentException('maxPointsTeam = '.print_r($maxPointsTeam,true));

		return $this->execute(ucfirst(__FUNCTION__), array($maxPointsTeam), $multicall);
	}

	/**
	 * Get the current and next number of maximum points per round for team mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getMaxPointsTeam()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set if new rules are used for team mode.
	 * Requires a challenge restart to be taken into account.
	 * @param bool $useNewRulesTeam
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setUseNewRulesTeam($useNewRulesTeam, $multicall = false)
	{
		if(!is_bool($useNewRulesTeam))
		throw new InvalidArgumentException('useNewRulesTeam = '.print_r($useNewRulesTeam,true));

		return $this->execute(ucfirst(__FUNCTION__), array($useNewRulesTeam), $multicall);
	}

	/**
	 * Get if the new rules are used for team mode (Current and next values).
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getUseNewRulesTeam()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the points needed for victory in Cup mode.
	 * Requires a challenge restart to be taken into account.
	 * @param int $pointsLimit
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setCupPointsLimit($pointsLimit, $multicall = false)
	{
		if(!is_int($pointsLimit))
		throw new InvalidArgumentException('pointsLimit = '.print_r($pointsLimit,true));

		return $this->execute(ucfirst(__FUNCTION__), array($pointsLimit), $multicall);
	}

	/**
	 * Get the points needed for victory in Cup mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getCupPointsLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Sets the number of rounds before going to next challenge in Cup mode.
	 * Requires a challenge restart to be taken into account.
	 * @param int $roundsPerChallenge
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setCupRoundsPerChallenge($roundsPerChallenge, $multicall = false)
	{
		if(!is_int($roundsPerChallenge))
		throw new InvalidArgumentException('roundsPerChallenge = '.print_r($roundsPerChallenge,true));

		return $this->execute(ucfirst(__FUNCTION__), array($roundsPerChallenge), $multicall);
	}

	/**
	 * Get the number of rounds before going to next challenge in Cup mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @param bool $multicall
	 * @return array
	 */
	function getCupRoundsPerChallenge($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether to enable the automatic warm-up phase in Cup mode.
	 * 0 = no, otherwise it's the duration of the phase, expressed in number of rounds.
	 * Requires a challenge restart to be taken into account.
	 * @param int $warmUpDuration
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setCupWarmUpDuration($warmUpDuration, $multicall = false)
	{
		if(!is_int($warmUpDuration))
		throw new InvalidArgumentException('warmUpDuration = '.print_r($warmUpDuration,true));

		return $this->execute(ucfirst(__FUNCTION__), array($warmUpDuration), $multicall);
	}

	/**
	 * Get whether the automatic warm-up phase is enabled in Cup mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getCupWarmUpDuration()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the number of winners to determine before the match is considered over.
	 * Requires a challenge restart to be taken into account.
	 * @param int $nbWinners
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setCupNbWinners($nbWinners, $multicall = false)
	{
		if(!is_int($nbWinners))
		throw new InvalidArgumentException('nbWinners = '.print_r($nbWinners,true));

		return $this->execute(ucfirst(__FUNCTION__), array($nbWinners), $multicall);
	}

	/**
	 * Get the number of winners to determine before the match is considered over.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getCupNbWinners()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the current challenge index in the selection, or -1 if the challenge is no longer in the selection.
	 * @return int
	 */
	function getCurrentChallengeIndex()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the challenge index in the selection that will be played next (unless the current one is restarted...)
	 * @return int
	 */
	function getNextChallengeIndex()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Sets the challenge index in the selection that will be played next (unless the current one is restarted...)
	 * @param int $nextChallengeIndex
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setNextChallengeIndex($nextChallengeIndex, $multicall = false)
	{
		if(!is_int($nextChallengeIndex))
		throw new InvalidArgumentException('nextChallengeIndex = '.print_r($nextChallengeIndex,true));

		return $this->execute(ucfirst(__FUNCTION__), array($nextChallengeIndex), $multicall);
	}

	/**
	 * Returns a struct containing the infos for the current challenge.
	 * The struct contains the following fields : Name, UId, FileName,
	 * Author, Environnement, Mood, BronzeTime, SilverTime, GoldTime,
	 * AuthorTime, CopperPrice, LapRace, NbLaps and NbCheckpoints.
	 * @return Challenge
	 */
	function getCurrentChallengeInfo()
	{
		return Structures\Challenge::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns a struct containing the infos for the next challenge.
	 * The struct contains the following fields : Name, UId, FileName,
	 * Author, Environnement, Mood, BronzeTime, SilverTime, GoldTime,
	 * AuthorTime, CopperPrice, LapRace, NbLaps and NbCheckpoints.
	 * (NbLaps and NbCheckpoints are also present but always set to -1)
	 * @return Challenge
	 */
	function getNextChallengeInfo()
	{
		return Structures\Challenge::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns a struct containing the infos for the challenge with the specified filename.
	 * The struct contains the following fields : Name, UId, FileName,
	 * Author, Environnement, Mood, BronzeTime, SilverTime, GoldTime,
	 * AuthorTime, CopperPrice, LapRace, NbLaps and NbCheckpoints.
	 * (NbLaps and NbCheckpoints are also present but always set to -1)
	 * @param string $filename
	 * @return Challenge
	 * @throws InvalidArgumentException
	 */
	function getChallengeInfo($filename)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return Structures\Challenge::fromArray($this->execute(ucfirst(__FUNCTION__),array($filename)));
	}

	/**
	 * Returns a boolean if the challenge with the specified filename matches the current server settings.
	 * @param string $filename
	 * @return bool
	 */
	function checkChallengeForCurrentServerParams($filename)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename));
	}

	/**
	 * Returns a list of challenges among the current selection of the server.
	 * This method take two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the selection.
	 * The list is an array of structures. Each structure contains the following fields : Name, UId, FileName, Environnement, Author, GoldTime and CopperPrice.
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @return array[Challenge] The list is an array of Challenge
	 * @throws InvalidArgumentException
	 */
	function getChallengeList($length, $offset)
	{
		if(!is_int($length))
		throw new InvalidArgumentException('length = '.print_r($length,true));
		if(!is_int($offset))
		throw new InvalidArgumentException('offset = '.print_r($offset,true));

		return Structures\Challenge::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset)));
	}

	/**
	 * Add the challenge with the specified filename at the end of the current selection.
	 * @param string $filename
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function addChallenge($filename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Add the list of challenges with the specified filename at the end of the current selection.
	 * @param array $filenames
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function addChallengeList(array $filenames, $multicall = false)
	{
		if(!is_array($filenames))
		throw new InvalidArgumentException('filenames = '.print_r($filenames,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filenames), $multicall);
	}

	/**
	 * Remove the challenge with the specified filename from the current selection.
	 * @param string $filename
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function removeChallenge($filename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Remove the list of challenges with the specified filenames from the current selection.
	 * The list of challenges to remove is an array of strings.
	 * @param array $filenames
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function removeChallengeList(array $filenames, $multicall = false)
	{
		if(!is_array($filenames))
		throw new InvalidArgumentException('filenames = '.print_r($filenames,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filenames), $multicall);
	}

	/**
	 * Insert the challenge with the specified filename after the current challenge.
	 * @param string $filename
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function insertChallenge($filename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Insert the list of challenges with the specified filenames after the current challenge.
	 * The list of challenges to remove is an array of strings.
	 * @param array $filenames
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function insertChallengeList(array $filenames, $multicall = false)
	{
		if(!is_array($filenames))
		throw new InvalidArgumentException('filenames = '.print_r($filenames,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filenames), $multicall);
	}

	/**
	 * Set as next challenge the one with the specified filename, if it is present in the selection.
	 * @param string $filename
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function chooseNextChallenge($filename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}
	
	/**
	 * Set as next challenges the list of challenges with the specified filenames, if they are present in the selection.
	 * The list of challenges to remove is an array of strings.
	 * @param array $filenames
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function chooseNextChallengeList(array $filenames, $multicall = false)
	{
		if(!is_array($filenames))
		throw new InvalidArgumentException('filenames = '.print_r($filenames,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filenames), $multicall);
	}
	
	/**
	 * Set a list of challenges defined in the playlist with the specified filename
	 * as the current selection of the server, and load the gameinfos from the same file.
	 * @param string $filename
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function loadMatchSettings($filename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename));
	}

	/**
	 * Add a list of challenges defined in the playlist with the specified filename at the end of the current selection.
	 * @param string $filename
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function appendPlaylistFromMatchSettings($filename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Save the current selection of challenge in the playlist with the specified filename, as well as the current gameinfos.
	 * @param string $filename
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function saveMatchSettings($filename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Insert a list of challenges defined in the playlist with the specified filename after the current challenge.
	 * @param string $filename
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function insertPlaylistFromMatchSettings($filename, $multicall = false)
	{
		if(!is_string($filename))
		throw new InvalidArgumentException('filename = '.print_r($filename,true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Returns the list of players on the server. This method take two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the list,
	 * an optional 3rd parameter is used for compatibility: struct version (0 = united, 1 = forever, 2 = forever, including the servers).
	 * The list is an array of ManiaLive\DedicatedApi\Structures\Player.
	 * LadderRanking is 0 when not in official mode,
	 * Flags = ForceSpectator(0,1,2) + IsReferee * 10 + IsPodiumReady * 100 + IsUsingStereoscopy * 1000 +
	 * IsManagedByAnOtherServer * 10000 + IsServer * 100000 + HasPlayerSlot * 1000000
	 * SpectatorStatus = Spectator + TemporarySpectator * 10 + PureSpectator * 100 + AutoTarget * 1000 + CurrentTargetId * 10000
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @param int $compatibility
	 * @return array[ManiaLive\DedicatedApi\Structures\Player] The list is an array of ManiaLive\DedicatedApi\Structures\Player
	 * @throws InvalidArgumentException
	 */
	function getPlayerList($length, $offset, $compatibility = 1)
	{
		if(!is_int($length))
		throw new InvalidArgumentException('length = '.print_r($length,true));
		if(!is_int($offset))
		throw new InvalidArgumentException('offset = '.print_r($offset,true));
		if(!is_int($compatibility))
		throw new InvalidArgumentException('compatibility = '.print_r($compatibility,true));

		return Structures\Player::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset, $compatibility)));
	}

	/**
	 * Returns a object of type ManiaLive\DedicatedApi\Structures\Player containing the infos on the player with the specified login,
	 * with an optional parameter for compatibility: struct version (0 = united, 1 = forever).
	 * The structure is identical to the ones from GetPlayerList. Forever PlayerInfo struct is:
	 * Login, NickName, PlayerId, TeamId, SpectatorStatus, LadderRanking, and Flags.
	 * LadderRanking is 0 when not in official mode,
	 * Flags = ForceSpectator(0,1,2) + IsReferee * 10 + IsPodiumReady * 100 + IsUsingStereoscopy * 1000 +
	 * IsManagedByAnOtherServer * 10000 + IsServer * 100000 + HasPlayerSlot * 1000000
	 * SpectatorStatus = Spectator + TemporarySpectator * 10 + PureSpectator * 100 + AutoTarget * 1000 + CurrentTargetId * 10000
	 * @param int $playerLogin
	 * @param int $compatibility
	 * @return \ManiaLive\DedicatedApi\Structures\Player
	 * @throws InvalidArgumentException
	 */
	function getPlayerInfo($playerLogin, $compatibility = 1)
	{
		if(!is_string($playerLogin))
		throw new InvalidArgumentException('playerLogin = '.print_r($playerLogin,true));
		if(!is_int($compatibility))
		throw new InvalidArgumentException('compatibility = '.print_r($compatibility,true));

		return Structures\Player::fromArray($this->execute(ucfirst(__FUNCTION__), array($playerLogin, $compatibility)));
	}

	/**
	 * Returns an object of type ManiaLive\DedicatedApi\Structures\Player containing the infos on the player with the specified login.
	 * The structure contains the following fields :
	 * Login, NickName, PlayerId, TeamId, IPAddress, DownloadRate, UploadRate, Language, IsSpectator,
	 * IsInOfficialMode, a structure named Avatar, an array of structures named Skins, a structure named LadderStats,
	 * HoursSinceZoneInscription and OnlineRights (0: nations account, 3: united account).
	 * Each structure of the array Skins contains two fields Environnement and a struct PackDesc.
	 * Each structure PackDesc, as well as the struct Avatar, contains two fields FileName and Checksum.
	 * @param int $playerLogin
	 * @return ManiaLive\DedicatedApi\Structures\Player
	 * @throws InvalidArgumentException
	 */
	function getDetailedPlayerInfo($playerLogin)
	{
		if(!is_string($playerLogin))
		throw new InvalidArgumentException('playerLogin = '.print_r($playerLogin,true));

		return Structures\Player::fromArray($this->execute(ucfirst(__FUNCTION__), array($playerLogin)));
	}

	/**
	 * Returns an object of ManiaLive\DedicatedApi\Structures\Player type containing the infos on the player with the specified login.
	 * The structure contains the following fields : Login, NickName, PlayerId, TeamId, IPAddress, DownloadRate, UploadRate,
	 * Language, IsSpectator, IsInOfficialMode, a structure named Avatar, an array of structures named Skins, a structure named LadderStats,
	 * HoursSinceZoneInscription and OnlineRights (0: nations account, 3: united account).
	 * Each structure of the array Skins contains two fields Environnement and a struct PackDesc.
	 * Each structure PackDesc, as well as the struct Avatar, contains two fields FileName and Checksum.
	 * @param int $compatibility
	 * @return ManiaLive\DedicatedApi\Structures\Player
	 * @throws InvalidArgumentException
	 */
	function getMainServerPlayerInfo($compatibility = 1)
	{
		if(!is_int($compatibility))
		throw new InvalidArgumentException('compatibility = '.print_r($compatibility,true));

		return Structures\Player::fromArray($this->execute(ucfirst(__FUNCTION__), array($compatibility)));
	}

	/**
	 * Returns the current ranking for the race in progress. This method take two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the ranking.
	 * The ranking returned is a list of ManiaLive\DedicatedApi\Structures\Player.
	 * It also contains an array BestCheckpoints that contains the checkpoint times for the best race.
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @return array[ManiaLive\DedicatedApi\Structures\Player] The list is an array of ManiaLive\DedicatedApi\Structures\Player.
	 * @throws InvalidArgumentException
	 */
	function getCurrentRanking($length, $offset)
	{
		if(!is_int($length))
		throw new InvalidArgumentException('length = '.print_r($length,true));
		if(!is_int($offset))
		throw new InvalidArgumentException('offset = '.print_r($offset,true));

		return Structures\Player::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset)));
	}

	/**
	 * Force the scores of the current game. Only available in rounds and team mode.
	 * You have to pass an array of structs {int PlayerId, int Score}. And a boolean SilentMode -
	 * if true, the scores are silently updated (only available for SuperAdmin), allowing an external controller to do its custom counting...
	 * @param array $scores
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function forceScores(array $scores, $silentMode = false, $multicall = false)
	{
		if(!is_array($scores))
		throw new InvalidArgumentException('scores = '.print_r($scores,true));

		for($i = 0; $i < count($scores); $i++)
		{
			if(!is_int($scores[$i]['PlayerId']))
			throw new InvalidArgumentException('score['.$i.'][\'PlayerId\'] = '.print_r($scores[$i]['PlayerId'],true));
			if(!is_int($scores[$i]['Score']))
			throw new InvalidArgumentException('score['.$i.'][\'Score\'] = '.print_r($scores[$i]['Score'],true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($scores, $silentMode), $multicall);
	}

	/**
	 * Force the team of the player. Only available in team mode. You have to pass the login and the team number (0 or 1).
	 * @param string $playerLogin
	 * @param int $teamNumber
	 * @param bool $multicall
	 * @return bool
	 */
	function forcePlayerTeam(Player $player, $teamNumber, $multicall = false)
	{
		if($teamNumber !== 0 && $teamNumber !== 1)
		throw new InvalidArgumentException('teamNumber = '.print_r($teamNumber,true));

		return $this->execute('ForcePlayerTeamId', array($player->playerId, $teamNumber), $multicall);
	}

	/**
	 * Force the spectating status of the player. You have to pass the login and the spectator mode (0: user selectable, 1: spectator, 2: player).
	 * @param Player $player
	 * @param int $spectatorMode
	 * @param bool $multicall
	 * @return bool
	 */
	function forceSpectator(Player $player, $spectatorMode, $multicall = false)
	{
		if($spectatorMode !== 0 && $spectatorMode !== 1 && $spectatorMode !== 2)
		throw new InvalidArgumentException('spectatorMode = '.print_r($spectatorMode,true));

		return $this->execute('ForceSpectatorId', array($player->playerId, $spectatorMode), $multicall);
	}

	/**
	 * Force spectators to look at a specific player. You have to pass the login of the spectator (or '' for all) and
	 * the login of the target (or '' for automatic), and an integer for the camera type to use (-1 = leave unchanged, 0 = replay, 1 = follow, 2 = free).
	 * @param Player $player
	 * @param Player $target
	 * @param int $cameraType
	 * @param bool $multicall
	 * @return bool
	 */
	function forceSpectatorTarget(Player $player,Player $target, $cameraType, $multicall = false)
	{
		if($cameraType !== -1 && $cameraType !== 0 && $cameraType !== 1 && $cameraType !== 2)
		throw new InvalidArgumentException('cameraType = '.print_r($cameraType,true));

		return $this->execute('ForceSpectatorTargetId', array($player->playerId, $target->playerId, $cameraType), $multicall);
	}

	/**
	 * Pass the login of the spectator. A spectator that once was a player keeps his player slot, so that he can go back to race mode.
	 * Calling this function frees this slot for another player to connect.
	 * @param Player $player
	 * @param bool $multicall
	 * @return bool
	 */
	function spectatorReleasePlayerSlot(Player $player, $multicall = false)
	{
		return $this->execute('SpectatorReleasePlayerSlotId', array($player->playerId), $multicall);
	}

	/**
	 * Enable control of the game flow: the game will wait for the caller to validate state transitions.
	 * @param bool $flowControlEnable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function manualFlowControlEnable($flowControlEnable, $multicall = false)
	{
		if(!is_bool($flowControlEnable))
		throw new InvalidArgumentException('flowControlEnable = '.print_r($flowControlEnable,true));

		return $this->execute(ucfirst(__FUNCTION__), array($flowControlEnable), $multicall);
	}

	/**
	 * Allows the game to proceed.
	 * @param bool $multicall
	 * @return bool
	 */
	function manualFlowControlProceed($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns whether the manual control of the game flow is enabled. 0 = no, 1 = yes by the xml-rpc client making the call, 2 = yes, by some other xml-rpc client.
	 * @return int
	 */
	function manualFlowControlIsEnabled()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the transition that is currently blocked, or '' if none. (That's exactly the value last received by the callback.)
	 * @return string
	 */
	function manualFlowControlGetCurTransition()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the transition that is currently blocked, or '' if none. (That's exactly the value last received by the callback.)
	 * @return string
	 */
	function checkEndMatchCondition()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns an object NetworkStats.
	 * The structure contains the following fields : Uptime, NbrConnection, MeanConnectionTime, MeanNbrPlayer,
	 * RecvNetRate, SendNetRate, TotalReceivingSize, TotalSendingSize and an array of structures named PlayerNetInfos.
	 * Each structure of the array PlayerNetInfos is a ManiaLive\DedicatedApi\Structures\Player object contains the following fields : Login, IPAddress, LastTransferTime, DeltaBetweenTwoLastNetState, PacketLossRate.
	 * @return \ManiaLive\DedicatedApi\Structures\NetworkStats
	 */
	function getNetworkStats()
	{
		return Structures\NetworkStats::fromArray($this->execute(ucfirst(__FUNCTION__)));;
	}

	/**
	 * Start a server on lan, using the current configuration.
	 * @param bool $multicall
	 * @return bool
	 */
	function startServerLan($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Start a server on internet using the 'Login' and 'Password' specified in the struct passed as parameters.
	 * @param array $ids
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function startServerInternet(array $ids, $multicall = false)
	{
		if(!is_array($ids) && !array_key_exists('Login', $ids) && !array_key_exists('Password', $ids))
		throw new InvalidArgumentException('ids = '.print_r($ids,true));

		return $this->execute(ucfirst(__FUNCTION__), array($ids), $multicall);
	}

	/**
	 * Returns the current status of the server.
	 * @return ManiaLive\DedicatedApi\Structures\Status
	 */
	function getStatus()
	{
		return Status::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Quit the application.
	 * @param bool $multicall
	 * @return bool
	 */
	function quitGame($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the path of the game datas directory.
	 * @return string
	 */
	function gameDataDirectory()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the path of the tracks directory.
	 * @return string
	 */
	function getTracksDirectory()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the path of the skins directory.
	 * @return string
	 */
	function getSkinsDirectory()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}


}

/**
 *
 * Exception Dedicated to Query Error
 * @author Philippe Melot
 */
class QueryException extends \Exception {}

/**
 *
 * Exception Dedicated to Connection Error
 * @author Philippe Melot
 */
class ConnectionException extends \Exception {}

/**
 *
 * Exception Dedicated to Invalid Argument Error on Request Call
 * @author Philippe Melot
 *
 */
class InvalidArgumentException extends \Exception {}
?>