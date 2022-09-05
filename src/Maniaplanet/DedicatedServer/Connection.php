<?php

declare(strict_types=1);
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

namespace Maniaplanet\DedicatedServer;

use Maniaplanet\DedicatedServer\Structures\ServerOptions;

/**
 * Dedicated Server Connection Instance
 * Methods returns nothing if $multicall = true
 */
class Connection
{
    const API_2011_02_21 = '2011-02-21';
    const API_2011_08_01 = '2011-08-01';
    const API_2011_10_06 = '2011-10-06';
    const API_2012_06_19 = '2012-06-19';
    const API_2013_04_16 = '2013-04-16';

    /** @var int[] */
    private static $levels = [
        null => -1,
        'User' => 0,
        'Admin' => 1,
        'SuperAdmin' => 2
    ];
    /** @var Xmlrpc\GbxRemote */
    protected $xmlrpcClient;
    /** @var string */
    protected $user;
    /** @var callable[] */
    private $multicallHandlers = [];

    public function __construct(
        $host = '127.0.0.1',
        $port = 5000,
        $timeout = 5,
        $user = 'SuperAdmin',
        $password = 'SuperAdmin',
        $apiVersion = self::API_2013_04_16
    ) {
        $this->xmlrpcClient = new Xmlrpc\GbxRemote($host, $port, $timeout);
        $this->authenticate($user, $password);
        if ($apiVersion > self::API_2011_02_21) {
            $this->setApiVersion($apiVersion);
        }
    }

    /**
     * Allow user authentication by specifying a login and a password, to gain access to the set of functionalities corresponding to this authorization level.
     * @param string $user
     * @param string $password
     * @return bool
     * @throws InvalidArgumentException
     */
    public function authenticate($user, $password)
    {
        if (!is_string($user) || !isset(self::$levels[$user])) {
            throw new InvalidArgumentException('user = ' . print_r($user, true));
        }
        if (self::$levels[$this->user] >= self::$levels[$user]) {
            return true;
        }

        if (!is_string($password)) {
            throw new InvalidArgumentException('password = ' . print_r($password, true));
        }

        $res = $this->execute(ucfirst(__FUNCTION__), [$user, $password]);
        if ($res) {
            $this->user = $user;
        }
        return $res;
    }

    /**
     * Add a call in queue. It will be executed by the next Call from the user to executeMulticall
     * @param string $methodName
     * @param mixed[] $params
     * @param bool|callable $multicall True to queue the request or false to execute it immediately
     * @return mixed
     */
    public function execute($methodName, $params = [], $multicall = false)
    {
        if ($multicall) {
            $this->xmlrpcClient->addCall($methodName, $params);
            $this->multicallHandlers[] = $multicall;
        } else {
            return $this->xmlrpcClient->query($methodName, $params);
        }
    }

    /**
     * Define the wanted api.
     * @param string $version
     * @param bool $multicall
     * @return bool
     */
    public function setApiVersion(string $version, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$version], $multicall);
    }

    /**
     * Close the current socket connexion
     * Never call this method, use instead DedicatedApi::delete($host, $port)
     */
    public function terminate()
    {
        $this->xmlrpcClient->terminate();
    }

    /**
     * Change client timeouts
     * @param int $read read timeout (in ms), 0 to leave unchanged
     * @param int $write write timeout (in ms), 0 to leave unchanged
     */
    public function setTimeouts($read = null, $write = null)
    {
        $this->xmlrpcClient->setTimeouts($read, $write);
    }

    /**
     * @return int Network idle time in seconds
     */
    public function getIdleTime()
    {
        return $this->xmlrpcClient->getIdleTime();
    }

    /**
     * Return pending callbacks
     * @return mixed[]
     */
    public function executeCallbacks()
    {
        return $this->xmlrpcClient->getCallbacks();
    }

    /**
     * Execute the calls in queue and return the result
     * @return mixed[]
     */
    public function executeMulticall()
    {
        $responses = $this->xmlrpcClient->multiquery();
        foreach ($responses as $i => &$response) {
            if (!($response instanceof Xmlrpc\FaultException) && is_callable($this->multicallHandlers[$i])) {
                $response = call_user_func($this->multicallHandlers[$i], $response);
            }
        }
        $this->multicallHandlers = [];
        return $responses;
    }

    /**
     * Change the password for the specified login/user.
     * Only available to SuperAdmin.
     * @param string $user
     * @param string $password
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function changeAuthPassword(string $user, string $password, $multicall = false)
    {
        if (!isset(self::$levels[$user])) {
            throw new InvalidArgumentException('user = ' . print_r($user, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$user, $password], $multicall);
    }

    /**
     * Allow the GameServer to call you back.
     * @param bool $enable
     * @param bool $multicall
     * @return bool
     */
    public function enableCallbacks(bool $enable = true, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$enable], $multicall);
    }

    /**
     * Returns a struct with the Name, TitleId, Version, Build and ApiVersion of the application remotely controlled.
     * @param bool $multicall
     * @return Structures\Version
     */
    public function getVersion($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('Version'));
        }
        return Structures\Version::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * @param string $struct
     * @param bool $array
     * @return callable
     */
    private function structHandler($struct, $array = false)
    {
        return ['\\' . __NAMESPACE__ . '\Structures\\' . $struct, 'fromArray' . ($array ? 'OfArray' : '')];
    }

    /**
     * Returns the current status of the server.
     * @param bool $multicall
     * @return Structures\Status
     */
    public function getStatus($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('Status'));
        }
        return Structures\Status::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Quit the application.
     * Only available to SuperAdmin.
     * @param bool $multicall
     * @return bool
     */
    public function quitGame($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Call a vote to kick a player.
     * You can additionally supply specific parameters for this vote: a ratio, a time out and who is voting.
     * Only available to Admin.
     * @param mixed $player A player object or a login
     * @param float $ratio In range [0,1] or -1 for default ratio
     * @param int $timeout In milliseconds, 0 for default timeout, 1 for indefinite
     * @param int $voters 0: active players, 1: any player, 2: everybody including pure spectators
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function callVoteKick($player, $ratio = 0.5, $timeout = 0, $voters = 1, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        $vote = new Structures\Vote(Structures\VoteRatio::COMMAND_KICK, [$login]);
        return $this->callVote($vote, $ratio, $timeout, $voters, $multicall);
    }

    /**
     * Returns the login of the given player
     * @param mixed $player
     * @return string|bool
     */
    private function getLogin($player, $allowEmpty = false)
    {
        if (is_object($player)) {
            if (property_exists($player, 'login')) {
                $player = $player->login;
            } else {
                return false;
            }
        }
        if (empty($player)) {
            return $allowEmpty ? '' : false;
        }
        if (is_string($player)) {
            return $player;
        }
        return false;
    }

    /**
     * Call a vote for a command.
     * You can additionally supply specific parameters for this vote: a ratio, a time out and who is voting.
     * Only available to Admin.
     * @param Structures\Vote $vote
     * @param float $ratio In range [0,1] or -1 for default ratio
     * @param int $timeout In milliseconds, 0 for default timeout, 1 for indefinite
     * @param int $voters 0: active players, 1: any player, 2: everybody including pure spectators
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function callVote($vote, $ratio = -1., $timeout = 0, $voters = 1, $multicall = false)
    {
        if (!($vote instanceof Structures\Vote && $vote->isValid())) {
            throw new InvalidArgumentException('vote = ' . print_r($vote, true));
        }
        if (!Structures\VoteRatio::isRatio($ratio)) {
            throw new InvalidArgumentException('ratio = ' . print_r($ratio, true));
        }
        if (!is_int($timeout)) {
            throw new InvalidArgumentException('timeout = ' . print_r($timeout, true));
        }
        if (!is_int($voters) || $voters < 0 || $voters > 2) {
            throw new InvalidArgumentException('voters = ' . print_r($voters, true));
        }

        $xml = Xmlrpc\Request::encode($vote->cmdName, $vote->cmdParam, false);
        return $this->execute(ucfirst(__FUNCTION__) . 'Ex', [$xml, $ratio, $timeout, $voters], $multicall);
    }

    /**
     * Call a vote to ban a player.
     * You can additionally supply specific parameters for this vote: a ratio, a time out and who is voting.
     * Only available to Admin.
     * @param mixed $player A player object or a login
     * @param float $ratio In range [0,1] or -1 for default ratio
     * @param int $timeout In milliseconds, 0 for default timeout, 1 for indefinite
     * @param int $voters 0: active players, 1: any player, 2: everybody including pure spectators
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function callVoteBan($player, $ratio = 0.6, $timeout = 0, $voters = 1, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        $vote = new Structures\Vote(Structures\VoteRatio::COMMAND_BAN, [$login]);
        return $this->callVote($vote, $ratio, $timeout, $voters, $multicall);
    }

    /**
     * Call a vote to restart the current map.
     * You can additionally supply specific parameters for this vote: a ratio, a time out and who is voting.
     * Only available to Admin.
     * @param float $ratio In range [0,1] or -1 for default ratio
     * @param int $timeout In milliseconds, 0 for default timeout, 1 for indefinite
     * @param int $voters 0: active players, 1: any player, 2: everybody including pure spectators
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function callVoteRestartMap($ratio = 0.5, $timeout = 0, $voters = 1, $multicall = false)
    {
        $vote = new Structures\Vote(Structures\VoteRatio::COMMAND_RESTART_MAP);
        return $this->callVote($vote, $ratio, $timeout, $voters, $multicall);
    }

    /**
     * Call a vote to go to the next map.
     * You can additionally supply specific parameters for this vote: a ratio, a time out and who is voting.
     * Only available to Admin.
     * @param float $ratio In range [0,1] or -1 for default ratio
     * @param int $timeout In milliseconds, 0 for default timeout, 1 for indefinite
     * @param int $voters 0: active players, 1: any player, 2: everybody including pure spectators
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function callVoteNextMap($ratio = 0.5, $timeout = 0, $voters = 1, $multicall = false)
    {
        $vote = new Structures\Vote(Structures\VoteRatio::COMMAND_NEXT_MAP);
        return $this->callVote($vote, $ratio, $timeout, $voters, $multicall);
    }

    /**
     * Cancel the current vote.
     * Only available to Admin.
     * @param bool $multicall
     * @return bool
     */
    public function cancelVote($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns the vote currently in progress.
     * @param $multicall
     * @return Structures\Vote
     */
    public function getCurrentCallVote($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('Vote'));
        }
        return Structures\Vote::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Set a new timeout for waiting for votes.
     * Only available to Admin.
     * @param int $timeout In milliseconds, 0 to disable votes
     * @param bool $multicall
     * @return bool
     */
    public function setCallVoteTimeOut(int $timeout, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$timeout], $multicall);
    }

    /**
     * Get the current and next timeout for waiting for votes.
     * @param $multicall
     * @return int[] {int CurrentValue, int NextValue}
     */
    public function getCallVoteTimeOut($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set a new default ratio for passing a vote.
     * Only available to Admin.
     * @param float $ratio In range [0,1] or -1 to disable votes
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setCallVoteRatio($ratio, $multicall = false)
    {
        if (!Structures\VoteRatio::isRatio($ratio)) {
            throw new InvalidArgumentException('ratio = ' . print_r($ratio, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$ratio], $multicall);
    }

    /**
     * Get the current default ratio for passing a vote.
     * @param bool $multicall
     * @return float
     */
    public function getCallVoteRatio($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set the ratios list for passing specific votes, extended version with parameters matching.
     * Only available to Admin.
     * @param Structures\VoteRatio[] $ratios
     * @param bool $replaceAll True to override the whole ratios list or false to modify only specified ratios
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setCallVoteRatios(array $ratios, bool $replaceAll = true, $multicall = false)
    {
        foreach ($ratios as $i => &$ratio) {
            if (!($ratio instanceof Structures\VoteRatio && $ratio->isValid())) {
                throw new InvalidArgumentException('ratios[' . $i . '] = ' . print_r($ratios, true));
            }
            $ratio = $ratio->toArray();
        }

        return $this->execute(ucfirst(__FUNCTION__) . 'Ex', [$replaceAll, $ratios], $multicall);
    }

    /**
     * Get the current ratios for passing votes, extended version with parameters matching.
     * @param bool $multicall
     * @return Structures\VoteRatio[]
     */
    public function getCallVoteRatios($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__) . 'Ex', [], $this->structHandler('VoteRatio', true));
        }
        return Structures\VoteRatio::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__) . 'Ex'));
    }

    /**
     * Send a text message, possibly localised to a specific login or to everyone, without the server login.
     * Only available to Admin.
     * @param string|string[][] $message Single string or array of structures {Lang='xx', Text='...'}:
     * if no matching language is found, the last text in the array is used
     * @param mixed $recipient Login, player object or array; null for all
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function chatSendServerMessage($message, $recipient = null, $multicall = false)
    {
        $logins = $this->getLogins($recipient, true);
        if ($logins === false) {
            throw new InvalidArgumentException('recipient = ' . print_r($recipient, true));
        }

        if (is_array($message)) {
            return $this->execute(ucfirst(__FUNCTION__) . 'ToLanguage', [$message, $logins], $multicall);
        }
        if (is_string($message)) {
            if ($logins) {
                return $this->execute(ucfirst(__FUNCTION__) . 'ToLogin', [$message, $logins], $multicall);
            }
            return $this->execute(ucfirst(__FUNCTION__), [$message], $multicall);
        }
        // else
        throw new InvalidArgumentException('message = ' . print_r($message, true));
    }

    /**
     * Returns logins of given players
     * @param mixed $players
     * @return string|bool
     */
    private function getLogins($players, $allowEmpty = false)
    {
        if (is_array($players)) {
            $logins = [];
            foreach ($players as $player) {
                $login = $this->getLogin($player);
                if ($login === false) {
                    return false;
                }
                $logins[] = $login;
            }

            return implode(',', $logins);
        }
        return $this->getLogin($players, $allowEmpty);
    }

    /**
     * Send a text message, possibly localised to a specific login or to everyone.
     * Only available to Admin.
     * @param string|string[][] $message Single string or array of structures {Lang='xx', Text='...'}:
     * if no matching language is found, the last text in the array is used
     * @param mixed $recipient Login, player object or array; null for all
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function chatSend($message, $recipient = null, $multicall = false)
    {
        $logins = $this->getLogins($recipient, true);
        if ($logins === false) {
            throw new InvalidArgumentException('recipient = ' . print_r($recipient, true));
        }

        if (is_array($message)) {
            return $this->execute(ucfirst(__FUNCTION__) . 'ToLanguage', [$message, $logins], $multicall);
        }
        if (is_string($message)) {
            if ($logins) {
                return $this->execute(ucfirst(__FUNCTION__) . 'ToLogin', [$message, $logins], $multicall);
            }
            return $this->execute(ucfirst(__FUNCTION__), [$message], $multicall);
        }
        // else
        throw new InvalidArgumentException('message = ' . print_r($message, true));
    }

    /**
     * Returns the last chat lines. Maximum of 40 lines.
     * Only available to Admin.
     * @param bool $multicall
     * @return string[]
     */
    public function getChatLines($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * The chat messages are no longer dispatched to the players, they only go to the rpc callback and the controller has to manually forward them.
     * Only available to Admin.
     * @param bool $enable
     * @param bool $excludeServer Allows all messages from the server to be automatically forwarded.
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function chatEnableManualRouting(bool $enable = true, bool $excludeServer = false, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$enable, $excludeServer], $multicall);
    }

    /**
     * Send a message to the specified recipient (or everybody if empty) on behalf of sender.
     * Only available if manual routing is enabled.
     * Only available to Admin.
     * @param string $message
     * @param mixed $sender Login or player object
     * @param mixed $recipient Login, player object or array; empty for all
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function chatForward(string $message, $sender, $recipient = null, $multicall = false)
    {
        $senderLogin = $this->getLogin($sender);
        if ($senderLogin === false) {
            throw new InvalidArgumentException('sender = ' . print_r($sender, true));
        }
        $recipientLogins = $this->getLogins($recipient, true);
        if ($recipientLogins === false) {
            throw new InvalidArgumentException('recipient = ' . print_r($recipient, true));
        }

        return $this->execute(ucfirst(__FUNCTION__) . 'ToLogin', [$message, $senderLogin, $recipientLogins], $multicall);
    }

    /**
     * Display a notice on all clients.
     * Only available to Admin.
     * @param mixed $recipient Login, player object or array; empty for all
     * @param string $message
     * @param mixed $avatar Login or player object whose avatar will be displayed; empty for none
     * @param int $variant 0: normal, 1: sad, 2: happy
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function sendNotice($recipient, $message, $avatar = null, $variant = 0, $multicall = false)
    {
        $logins = $this->getLogins($recipient, true);
        if ($logins === false) {
            throw new InvalidArgumentException('recipient = ' . print_r($recipient, true));
        }
        if (!is_string($message)) {
            throw new InvalidArgumentException('message = ' . print_r($message, true));
        }
        $avatarLogin = $this->getLogin($avatar, true);
        if ($avatarLogin === false) {
            throw new InvalidArgumentException('avatar = ' . print_r($avatar, true));
        }
        if (!is_int($variant) || $variant < 0 || $variant > 2) {
            throw new InvalidArgumentException('variant = ' . print_r($variant, true));
        }

        if ($logins) {
            return $this->execute(ucfirst(__FUNCTION__) . 'ToLogin', [$logins, $message, $avatarLogin, $variant], $multicall);
        }
        return $this->execute(ucfirst(__FUNCTION__), [$message, $avatar, $variant], $multicall);
    }

    /**
     * Display a manialink page on all clients.
     * Only available to Admin.
     * @param mixed $recipient Login, player object or array; empty for all
     * @param string $manialinks XML string
     * @param int $timeout Seconds before autohide, 0 for permanent
     * @param bool $hideOnClick Hide as soon as the user clicks on a page option
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function sendDisplayManialinkPage($recipient, string $manialinks, int $timeout = 0, bool $hideOnClick = false, $multicall = false)
    {
        $logins = $this->getLogins($recipient, true);
        if ($logins === false) {
            throw new InvalidArgumentException('recipient = ' . print_r($recipient, true));
        }

        if ($logins) {
            return $this->execute(ucfirst(__FUNCTION__) . 'ToLogin', [$logins, $manialinks, $timeout, $hideOnClick], $multicall);
        }
        return $this->execute(ucfirst(__FUNCTION__), [$manialinks, $timeout, $hideOnClick], $multicall);
    }

    /**
     * Hide the displayed manialink page.
     * Only available to Admin.
     * @param mixed $recipient Login, player object or array; empty for all
     * @param bool $multicall
     * @return bool
     */
    public function sendHideManialinkPage($recipient = null, $multicall = false)
    {
        $logins = $this->getLogins($recipient, true);
        if ($logins === false) {
            throw new InvalidArgumentException('recipient = ' . print_r($recipient, true));
        }

        if ($logins) {
            return $this->execute(ucfirst(__FUNCTION__) . 'ToLogin', [$logins], $multicall);
        }
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns the latest results from the current manialink page as an array of structs {string Login, int PlayerId, int Result}:
     * - Result == 0 -> no answer
     * - Result > 0 -> answer from the player.
     * @param bool $multicall
     * @return Structures\PlayerAnswer[]
     */
    public function getManialinkPageAnswers($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('PlayerAnswer', true));
        }
        return Structures\PlayerAnswer::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Opens a link in the client with the specified login.
     * Only available to Admin.
     * @param mixed $recipient Login, player object or array
     * @param string $link URL to open
     * @param int $linkType 0: in the external browser, 1: in the internal manialink browser
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function sendOpenLink($recipient, string $link, $linkType, $multicall = false)
    {
        $logins = $this->getLogins($recipient);
        if ($logins === false) {
            throw new InvalidArgumentException('recipient = ' . print_r($recipient, true));
        }
        if (!is_string($link)) {
            throw new InvalidArgumentException('link = ' . print_r($link, true));
        }
        if ($linkType !== 0 && $linkType !== 1) {
            throw new InvalidArgumentException('linkType = ' . print_r($linkType, true));
        }

        return $this->execute(ucfirst(__FUNCTION__) . 'ToLogin', [$logins, $link, $linkType], $multicall);
    }

    /**
     * Prior to loading next map, execute SendToServer url '#qjoin=login@title'
     * Only available to Admin.
     * Available since ManiaPlanet 4
     * @param      $link
     * @param bool $multicall
     * @return bool
     */
    public function sendToServerAfterMatchEnd(string $link, $multicall = false)
    {
        $link = str_replace("maniaplanet://", "", $link);

        return $this->execute(ucfirst(__FUNCTION__), [$link], $multicall);
    }

    /**
     * Kick the player with the specified login, with an optional message.
     * Only available to Admin.
     * @param mixed $player Login or player object
     * @param string $message
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function kick($player, $message = '', $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }
        if (!is_string($message)) {
            throw new InvalidArgumentException('message = ' . print_r($message, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login, $message], $multicall);
    }

    /**
     * Ban the player with the specified login, with an optional message.
     * Only available to Admin.
     * @param mixed $player Login or player object
     * @param string $message
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function ban($player, $message = '', $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }
        if (!is_string($message)) {
            throw new InvalidArgumentException('message = ' . print_r($message, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login, $message], $multicall);
    }

    /**
     * Ban the player with the specified login, with a message.
     * Add it to the black list, and optionally save the new list.
     * Only available to Admin.
     * @param mixed $player Login or player object
     * @param string $message
     * @param bool $save
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function banAndBlackList($player, string $message = '', bool $save = false, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$player, $message, $save], $multicall);
    }

    /**
     * Unban the player with the specified login.
     * Only available to Admin.
     * @param mixed $player Login or player object
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function unBan($player, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login], $multicall);
    }

    /**
     * Clean the ban list of the server.
     * Only available to Admin.
     * @param bool $multicall
     * @return bool
     */
    public function cleanBanList($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns the list of banned players.
     * @param int $length Maximum number of infos to be returned
     * @param int $offset Starting index in the list
     * @param bool $multicall
     * @return Structures\PlayerBan[]
     */
    public function getBanList(int $length = -1, int $offset = 0, $multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$length, $offset], $this->structHandler('PlayerBan', true));
        }
        return Structures\PlayerBan::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), [$length, $offset]));
    }

    /**
     * Blacklist the player with the specified login.
     * Only available to SuperAdmin.
     * @param mixed $player Login or player object
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function blackList($player, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login], $multicall);
    }

    /**
     * UnBlackList the player with the specified login.
     * Only available to SuperAdmin.
     * @param mixed $player Login or player object
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function unBlackList($player, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login], $multicall);
    }

    /**
     * Clean the blacklist of the server.
     * Only available to SuperAdmin.
     * @param bool $multicall
     * @return bool
     */
    public function cleanBlackList($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns the list of blacklisted players.
     * @param int $length Maximum number of infos to be returned
     * @param int $offset Starting index in the list
     * @param bool $multicall
     * @return Structures\Player[]
     */
    public function getBlackList(int $length = -1, int $offset = 0, $multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$length, $offset], $this->structHandler('Player', true));
        }
        return Structures\Player::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), [$length, $offset]));
    }

    /**
     * Load the black list file with the specified file name.
     * Only available to Admin.
     * @param string $filename Empty for default filename (blacklist.txt)
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function loadBlackList(string $filename = '', $multicall = false)
    {
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * @param string|string[] $filename
     * @return string|string[]
     */
    private function secureUtf8($filename)
    {
        if (is_string($filename)) {
            $filename = $this->stripBom($filename);
            if (preg_match('/[^\x09\x0A\x0D\x20-\x7E]/', $filename)) {
                return "\xEF\xBB\xBF" . $filename;
            }
            return $filename;
        }
        return array_map([$this, 'secureUtf8'], $filename);
    }

    /**
     * @param string|string[] $str
     * @return string|string[]
     */
    private function stripBom($str)
    {
        if (is_string($str)) {
            return str_replace("\xEF\xBB\xBF", '', $str);
        }
        return array_map([$this, 'stripBom'], $str);
    }

    /**
     * Save the black list in the file with specified file name.
     * Only available to Admin.
     * @param string $filename Empty for default filename (blacklist.txt)
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function saveBlackList(string $filename = '', $multicall = false)
    {
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Add the player with the specified login on the guest list.
     * Only available to Admin.
     * @param mixed $player Login or player object
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function addGuest($player, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login], $multicall);
    }

    /**
     * Remove the player with the specified login from the guest list.
     * Only available to Admin.
     * @param mixed $player Login or player object
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function removeGuest($player, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login], $multicall);
    }

    /**
     * Clean the guest list of the server.
     * Only available to Admin.
     * @param bool $multicall
     * @return bool
     */
    public function cleanGuestList($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns the list of players on the guest list.
     * @param int $length Maximum number of infos to be returned
     * @param int $offset Starting index in the list
     * @param bool $multicall
     * @return Structures\Player[]
     */
    public function getGuestList(int $length = -1, int $offset = 0, $multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$length, $offset], $this->structHandler('Player', true));
        }
        return Structures\Player::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), [$length, $offset]));
    }

    /**
     * Load the guest list file with the specified file name.
     * Only available to Admin.
     * @param string $filename Empty for default filename (guestlist.txt)
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function loadGuestList(string $filename = '', $multicall = false)
    {
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Save the guest list in the file with specified file name.
     * Only available to Admin.
     * @param string $filename Empty for default filename (guestlist.txt)
     * @param bool $multicall
     * @return bool
     */
    public function saveGuestList(string $filename = '', $multicall = false)
    {
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Sets whether buddy notifications should be sent in the chat.
     * Only available to Admin.
     * @param mixed $player Login or player object; empty for global setting
     * @param bool $enable
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setBuddyNotification($player, bool $enable, $multicall = false)
    {
        $login = $this->getLogin($player, true);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login, $enable], $multicall);
    }

    /**
     * Gets whether buddy notifications are enabled.
     * @param mixed $player Login or player object; empty for global setting
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function getBuddyNotification($player = null, $multicall = false)
    {
        $login = $this->getLogin($player, true);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login], $multicall);
    }

    /**
     * Write the data to the specified file.
     * Only available to Admin.
     * @param string $filename Relative to the Maps path
     * @param string $data
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function writeFile(string $filename, string $data, $multicall = false)
    {
        $filename = $this->secureUtf8($filename);

        $data = new Xmlrpc\Base64($data);
        return $this->execute(ucfirst(__FUNCTION__), [$filename, $data], $multicall);
    }

    /**
     * Send the data to the specified player. Login can be a single login or a list of comma-separated logins.
     * Only available to Admin.
     * @param mixed $recipient Login or player object or array
     * @param string $filename
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function tunnelSendDataFromFile($recipient, $filename, $multicall = false)
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException('filename = ' . print_r($filename, true));
        }

        $contents = file_get_contents($filename);
        return $this->tunnelSendData($recipient, $contents, $multicall);
    }

    /**
     * Send the data to the specified player. Login can be a single login or a list of comma-separated logins.
     * Only available to Admin.
     * @param mixed $recipient Login, player object or array
     * @param string $data
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function tunnelSendData($recipient, $data, $multicall = false)
    {
        $logins = $this->getLogins($recipient);
        if ($logins === false) {
            throw new InvalidArgumentException('recipient = ' . print_r($recipient, true));
        }
        if (!is_string($data)) {
            throw new InvalidArgumentException('data = ' . print_r($data, true));
        }

        $data = new Xmlrpc\Base64($data);
        return $this->execute(ucfirst(__FUNCTION__) . 'ToLogin', [$logins, $data], $multicall);
    }

    /**
     * Just log the parameters and invoke a callback.
     * Can be used to talk to other xmlrpc clients connected, or to make custom votes.
     * If used in a callvote, the first parameter will be used as the vote message on the clients.
     * Only available to Admin.
     * @param string $message
     * @param string $callback
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function dedicatedEcho($message, $callback = '', $multicall = false)
    {
        if (!is_string($message)) {
            throw new InvalidArgumentException('message = ' . print_r($message, true));
        }
        if (!is_string($callback)) {
            throw new InvalidArgumentException('callback = ' . print_r($callback, true));
        }

        return $this->execute('Echo', [$message, $callback], $multicall);
    }

    /**
     * Ignore the player with the specified login.
     * Only available to Admin.
     * @param mixed $player Login or player object
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function ignore($player, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login], $multicall);
    }

    /**
     * Unignore the player with the specified login.
     * Only available to Admin.
     * @param mixed $player Login or player object
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function unIgnore($player, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login], $multicall);
    }

    /**
     * Clean the ignore list of the server.
     * Only available to Admin.
     * @param bool $multicall
     * @return bool
     */
    public function cleanIgnoreList($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns the list of ignored players.
     * @param int $length Maximum number of infos to be returned
     * @param int $offset Starting index in the list
     * @param bool $multicall
     * @return Structures\Player[]
     * @throws InvalidArgumentException
     */
    public function getIgnoreList($length = -1, $offset = 0, $multicall = false)
    {
        if (!is_int($length)) {
            throw new InvalidArgumentException('length = ' . print_r($length, true));
        }
        if (!is_int($offset)) {
            throw new InvalidArgumentException('offset = ' . print_r($offset, true));
        }

        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$length, $offset], $this->structHandler('Player', true));
        }
        return Structures\Player::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), [$length, $offset]));
    }

    /**
     * Pay planets from the server account to a player.
     * The creation of the transaction itself may cost planets, so you need to have planets on the server account.
     * Only available to Admin.
     * @param mixed $payee Login or player object
     * @param int $amount
     * @param string $message
     * @param bool $multicall
     * @return int BillId
     * @throws InvalidArgumentException
     */
    public function pay($payee, $amount, $message = '', $multicall = false)
    {
        $login = $this->getLogin($payee);
        if ($login === false) {
            throw new InvalidArgumentException('payee = ' . print_r($payee, true));
        }
        if (!is_int($amount) || $amount < 1) {
            throw new InvalidArgumentException('amount = ' . print_r($amount, true));
        }
        if (!is_string($message)) {
            throw new InvalidArgumentException('message = ' . print_r($message, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login, $amount, $message], $multicall);
    }

    /**
     * Create a bill, send it to a player, and return the BillId.
     * The creation of the transaction itself may cost planets, so you need to have planets on the server account.
     * Only available to Admin.
     * @param mixed $payer Login or player object
     * @param int $amount
     * @param string $message
     * @param mixed $payee Login or player object; empty for server account
     * @param bool $multicall
     * @return int BillId
     * @throws InvalidArgumentException
     */
    public function sendBill($payer, $amount, $message = '', $payee = null, $multicall = false)
    {
        $payerLogin = $this->getLogin($payer);
        if ($payerLogin === false) {
            throw new InvalidArgumentException('payer = ' . print_r($payer, true));
        }
        if (!is_int($amount) || $amount < 1) {
            throw new InvalidArgumentException('amount = ' . print_r($amount, true));
        }
        if (!is_string($message)) {
            throw new InvalidArgumentException('message = ' . print_r($message, true));
        }
        $payeeLogin = $this->getLogin($payee, true);
        if ($payeeLogin === false) {
            throw new InvalidArgumentException('payee = ' . print_r($payee, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$payerLogin, $amount, $message, $payeeLogin], $multicall);
    }

    /**
     * Returns the current state of a bill.
     * @param int $billId
     * @param bool $multicall
     * @return Structures\Bill
     * @throws InvalidArgumentException
     */
    public function getBillState($billId, $multicall = false)
    {
        if (!is_int($billId)) {
            throw new InvalidArgumentException('billId = ' . print_r($billId, true));
        }

        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$billId], $this->structHandler('Bill'));
        }
        return Structures\Bill::fromArray($this->execute(ucfirst(__FUNCTION__), [$billId]));
    }

    /**
     * Returns the current number of planets on the server account.
     * @param bool $multicall
     * @return int
     */
    public function getServerPlanets($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Get some system infos, including connection rates (in kbps).
     * @param bool $multicall
     * @return Structures\SystemInfos
     */
    public function getSystemInfo($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('SystemInfos'));
        }
        return Structures\SystemInfos::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Set the download and upload rates (in kbps).
     * @param int $downloadRate
     * @param int $uploadRate
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setConnectionRates($downloadRate, $uploadRate, $multicall = false)
    {
        if (!is_int($downloadRate)) {
            throw new InvalidArgumentException('downloadRate = ' . print_r($downloadRate, true));
        }
        if (!is_int($uploadRate)) {
            throw new InvalidArgumentException('uploadRate = ' . print_r($uploadRate, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$downloadRate, $uploadRate], $multicall);
    }

    /**
     * Returns the list of tags and associated values set on this server.
     * Only available to Admin.
     * @param bool $multicall
     * @return Structures\Tag[]
     */
    public function getServerTags($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('Tag', true));
        }
        return Structures\Tag::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Set a tag and its value on the server.
     * Only available to Admin.
     * @param string $key
     * @param string $value
     * @param bool $multicall
     * @return bool
     */
    public function setServerTag(string $key, string $value, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$key, $value], $multicall);
    }

    /**
     * Unset the tag with the specified name on the server.
     * Only available to Admin.
     * @param string $key
     * @param bool $multicall
     * @return bool
     */
    public function unsetServerTag(string $key, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$key], $multicall);
    }

    /**
     * Reset all tags on the server.
     * Only available to Admin.
     * @param bool $multicall
     * @return bool
     */
    public function resetServerTags($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set a new server name in utf8 format.
     * Only available to Admin.
     * @param string $name
     * @param bool $multicall
     * @return bool
     */
    public function setServerName(string $name, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$name], $multicall);
    }

    /**
     * Get the server name in utf8 format.
     * @param bool $multicall
     * @return string
     */
    public function getServerName($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set a new server comment in utf8 format.
     * Only available to Admin.
     * @param string $comment
     * @param bool $multicall
     * @return bool
     */
    public function setServerComment(string $comment, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$comment], $multicall);
    }

    /**
     * Get the server comment in utf8 format.
     * @param bool $multicall
     * @return string
     */
    public function getServerComment($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set whether the server should be hidden from the public server list.
     * Only available to Admin.
     * @param int $visibility 0: visible, 1: always hidden, 2: hidden from nations
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setHideServer($visibility, $multicall = false)
    {
        if (!is_int($visibility) || $visibility < 0 || $visibility > 2) {
            throw new InvalidArgumentException('visibility = ' . print_r($visibility, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$visibility], $multicall);
    }

    /**
     * Get whether the server wants to be hidden from the public server list.
     * @param bool $multicall
     * @return int
     */
    public function getHideServer($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns true if this is a relay server.
     * @param bool $multicall
     * @return bool
     */
    public function isRelayServer($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set a new password for the server.
     * Only available to Admin.
     * @param string $password
     * @param bool $multicall
     * @return bool
     */
    public function setServerPassword(string $password, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$password], $multicall);
    }

    /**
     * Get the server password if called as Admin or Super Admin, else returns if a password is needed or not.
     * @param bool $multicall
     * @return string|bool
     */
    public function getServerPassword($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set a new password for the spectator mode.
     * Only available to Admin.
     * @param string $password
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setServerPasswordForSpectator(string $password, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$password], $multicall);
    }

    /**
     * Get the password for spectator mode if called as Admin or Super Admin, else returns if a password is needed or not.
     * @param bool $multicall
     * @return string|bool
     */
    public function getServerPasswordForSpectator($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set a new maximum number of players.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param int $maxPlayers
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setMaxPlayers(int $maxPlayers, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$maxPlayers], $multicall);
    }

    /**
     * Get the current and next maximum number of players allowed on server.
     * @param bool $multicall
     * @return int[] {int CurrentValue, int NextValue}
     */
    public function getMaxPlayers($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set a new maximum number of spectators.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param int $maxSpectators
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setMaxSpectators(int $maxSpectators, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$maxSpectators], $multicall);
    }

    /**
     * Get the current and next maximum number of Spectators allowed on server.
     * @param bool $multicall
     * @return int[] {int CurrentValue, int NextValue}
     */
    public function getMaxSpectators($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Declare if the server is a lobby, the number and maximum number of players currently managed by it, and the average level of the players.
     * Only available to Admin.
     * @param bool $isLobby
     * @param int $currentPlayers
     * @param int $maxPlayers
     * @param float $averageLevel
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setLobbyInfo($isLobby, $currentPlayers, $maxPlayers, $averageLevel, $multicall = false)
    {
        if (!is_bool($isLobby)) {
            throw new InvalidArgumentException('isLobby = ' . print_r($isLobby, true));
        }
        if (!is_int($currentPlayers)) {
            throw new InvalidArgumentException('currentPlayers = ' . print_r($currentPlayers, true));
        }
        if (!is_int($maxPlayers)) {
            throw new InvalidArgumentException('maxPlayers = ' . print_r($maxPlayers, true));
        }
        if (!is_float($averageLevel)) {
            throw new InvalidArgumentException('averageLevel = ' . print_r($averageLevel, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$isLobby, $currentPlayers, $maxPlayers, $averageLevel], $multicall);
    }

    /**
     * Get whether the server if a lobby, the number and maximum number of players currently managed by it.
     * @param bool $multicall
     * @return Structures\LobbyInfo
     */
    public function getLobbyInfo($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('LobbyInfo'));
        }
        return Structures\LobbyInfo::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Customize the clients 'leave server' dialog box.
     * Only available to Admin.
     * @param string $manialink
     * @param string $sendToServer Server URL, eg. '#qjoin=login@title'
     * @param bool $askFavorite
     * @param int $quitButtonDelay In milliseconds
     * @param bool $multicall
     * @return bool
     */
    public function customizeQuitDialog(string $manialink, string $sendToServer = '', bool $askFavorite = true, int $quitButtonDelay = 0, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$manialink, $sendToServer, $askFavorite, $quitButtonDelay], $multicall);
    }

    /**
     * Set whether, when a player is switching to spectator, the server should still consider him a player and keep his player slot, or not.
     * Only available to Admin.
     * @param bool $keep
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function keepPlayerSlots(bool $keep = true, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$keep], $multicall);
    }

    /**
     * Get whether the server keeps player slots when switching to spectator.
     * @param bool $multicall
     * @return bool
     */
    public function isKeepingPlayerSlots($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Enable or disable peer-to-peer upload from server.
     * Only available to Admin.
     * @param bool $enable
     * @param bool $multicall
     * @return bool
     */
    public function enableP2PUpload(bool $enable = true, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$enable], $multicall);
    }

    /**
     * Returns if the peer-to-peer upload from server is enabled.
     * @param bool $multicall
     * @return bool
     */
    public function isP2PUpload($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Enable or disable peer-to-peer download for server.
     * Only available to Admin.
     * @param bool $enable
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function enableP2PDownload($enable = true, $multicall = false)
    {
        if (!is_bool($enable)) {
            throw new InvalidArgumentException('enable = ' . print_r($enable, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$enable], $multicall);
    }

    /**
     * Returns if the peer-to-peer download for server is enabled.
     * @param bool $multicall
     * @return bool
     */
    public function isP2PDownload($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Allow clients to download maps from the server.
     * Only available to Admin.
     * @param bool $allow
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function allowMapDownload($allow = true, $multicall = false)
    {
        if (!is_bool($allow)) {
            throw new InvalidArgumentException('allow = ' . print_r($allow, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$allow], $multicall);
    }

    /**
     * Returns if clients can download maps from the server.
     * @param bool $multicall
     * @return bool
     */
    public function isMapDownloadAllowed($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns the path of the game datas directory.
     * Only available to Admin.
     * @param bool $multicall
     * @return string
     */
    public function gameDataDirectory($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], [$this, 'stripBom']);
        }
        return $this->stripBom($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Returns the path of the maps directory.
     * Only available to Admin.
     * @param bool $multicall
     * @return string
     */
    public function getMapsDirectory($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], [$this, 'stripBom']);
        }
        return $this->stripBom($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Returns the path of the skins directory.
     * Only available to Admin.
     * @param bool $multicall
     * @return string
     */
    public function getSkinsDirectory($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], [$this, 'stripBom']);
        }
        return $this->stripBom($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Return info for a given team.
     * Only available to Admin.
     * @param int $team 0: no clan, 1 or 2
     * @param bool $multicall
     * @return Structures\Team
     * @throws InvalidArgumentException
     */
    public function getTeamInfo($team, $multicall = false)
    {
        if (!is_int($team) || $team < 0 || $team > 2) {
            throw new InvalidArgumentException('team = ' . print_r($team, true));
        }

        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$team], $this->structHandler('Team'));
        }
        return Structures\Team::fromArray($this->execute(ucfirst(__FUNCTION__), [$team]));
    }

    /**
     * Set the clublinks to use for the two teams.
     * Only available to Admin.
     * @param string $team1
     * @param string $team2
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setForcedClubLinks(string $team1, string $team2, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$team1, $team2], $multicall);
    }

    /**
     * Get the forced clublinks.
     * @param bool $multicall
     * @return string[]
     */
    public function getForcedClubLinks($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * (debug tool) Connect a fake player to the server and returns the login.
     * Only available to Admin.
     * @param bool $multicall
     * @return string
     */
    public function connectFakePlayer($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * (debug tool) Disconnect a fake player.
     * Only available to Admin.
     * @param string $login Fake player login or '*' for all
     * @param bool $multicall
     * @return bool
     */
    public function disconnectFakePlayer(string $login, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$login], $multicall);
    }

    /**
     * Returns the token infos for a player.
     * @param mixed $player Login or player object
     * @param bool $multicall
     * @return Structures\TokenInfos
     * @throws InvalidArgumentException
     */
    public function getDemoTokenInfosForPlayer($player, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$login], $this->structHandler('TokenInfos'));
        }
        return Structures\TokenInfos::fromArray($this->execute(ucfirst(__FUNCTION__), [$login]));
    }

    /**
     * Disable player horns.
     * Only available to Admin.
     * @param bool $disable
     * @param bool $multicall
     * @return bool
     */
    public function disableHorns(bool $disable = true, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$disable], $multicall);
    }

    /**
     * Returns whether the horns are disabled.
     * @param bool $multicall
     * @return bool
     */
    public function areHornsDisabled($multicall = false): bool
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Disable the automatic mesages when a player connects/disconnects from the server.
     * Only available to Admin.
     * @param bool $disable
     * @param bool $multicall
     * @return bool
     */
    public function disableServiceAnnounces(bool $disable = true, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$disable], $multicall);
    }

    /**
     * Returns whether the automatic mesages are disabled.
     * @param bool $multicall
     * @return bool
     */
    public function areServiceAnnouncesDisabled($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Enable the autosaving of all replays (vizualisable replays with all players, but not validable) on the server.
     * Only available to SuperAdmin.
     * @param bool $enable
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function autoSaveReplays(bool $enable = true, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$enable], $multicall);
    }

    /**
     * Enable the autosaving on the server of validation replays, every time a player makes a new time.
     * Only available to SuperAdmin.
     * @param bool $enable
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function autoSaveValidationReplays($enable = true, $multicall = false)
    {
        if (!is_bool($enable)) {
            throw new InvalidArgumentException('enable = ' . print_r($enable, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$enable], $multicall);
    }

    /**
     * Returns if autosaving of all replays is enabled on the server.
     * @param bool $multicall
     * @return bool
     */
    public function isAutoSaveReplaysEnabled($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns if autosaving of validation replays is enabled on the server.
     * @param bool $multicall
     * @return bool
     */
    public function isAutoSaveValidationReplaysEnabled($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Saves the current replay (vizualisable replays with all players, but not validable).
     * Only available to Admin.
     * @param string $filename Empty for automatic filename
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function saveCurrentReplay(string $filename = '', $multicall = false)
    {
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Saves a replay with the ghost of all the players' best race.
     * Only available to Admin.
     * @param mixed $player Login or player object; empty for all
     * @param string $filename Empty for automatic filename
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function saveBestGhostsReplay($player = null, string $filename = '', $multicall = false)
    {
        $login = $this->getLogin($player, true);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$login, $filename], $multicall);
    }

    /**
     * Returns a replay containing the data needed to validate the current best time of the player.
     * @param mixed $player Login or player object
     * @param bool $multicall
     * @return string
     * @throws InvalidArgumentException
     */
    public function getValidationReplay($player, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$login], function ($v) {
                return $v->scalar;
            });
        }
        return $this->execute(ucfirst(__FUNCTION__), [$login])->scalar;
    }

    /**
     * Set a new ladder mode.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param int $mode 0: disabled, 1: forced
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setLadderMode($mode, $multicall = false)
    {
        if ($mode !== 0 && $mode !== 1) {
            throw new InvalidArgumentException('mode = ' . print_r($mode, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$mode], $multicall);
    }

    /**
     * Get the current and next ladder mode on server.
     * @param bool $multicall
     * @return int[] {int CurrentValue, int NextValue}
     */
    public function getLadderMode($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Get the ladder points limit for the players allowed on this server.
     * @param bool $multicall
     * @return Structures\LadderLimits
     */
    public function getLadderServerLimits($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('LadderLimits'));
        }
        return Structures\LadderLimits::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Set the network vehicle quality.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param int $quality 0: fast, 1: high
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setVehicleNetQuality($quality, $multicall = false)
    {
        if ($quality !== 0 && $quality !== 1) {
            throw new InvalidArgumentException('quality = ' . print_r($quality, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$quality], $multicall);
    }

    /**
     * Get the current and next network vehicle quality on server.
     * @param bool $multicall
     * @return int[] {int CurrentValue, int NextValue}
     */
    public function getVehicleNetQuality($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set new server options using the struct passed as parameters.
     * Mandatory fields:
     *  Name, Comment, Password, PasswordForSpectator, NextCallVoteTimeOut and CallVoteRatio.
     * Ignored fields:
     *  LadderServerLimitMin, LadderServerLimitMax and those starting with Current.
     * All other fields are optional and can be set to null to be ignored.
     * Only available to Admin.
     * A change of any field starting with Next requires a map restart to be taken into account.
     * @param Structures\ServerOptions $options
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setServerOptions(ServerOptions $options, $multicall = false)
    {
        if ($options->isValid() === false) {
            throw new InvalidArgumentException('options = ' . print_r($options, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$options->toSetterArray()], $multicall);
    }

    /**
     * Returns a struct containing the server options
     * @param bool $multicall
     * @return Structures\ServerOptions
     */
    public function getServerOptions($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('ServerOptions'));
        }
        return Structures\ServerOptions::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Set whether the players can choose their side or if the teams are forced by the server (using ForcePlayerTeam()).
     * Only available to Admin.
     * @param bool $enable
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setForcedTeams($enable, $multicall = false)
    {
        if (!is_bool($enable)) {
            throw new InvalidArgumentException('enable = ' . print_r($enable, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$enable], $multicall);
    }

    /**
     * Returns whether the players can choose their side or if the teams are forced by the server.
     * @param bool $multicall
     * @return bool
     */
    public function getForcedTeams($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Defines the packmask of the server.
     * Only maps matching the packmask will be allowed on the server, so that player connecting to it know what to expect.
     * Only available when the server is stopped.
     * Only available in 2011-08-01 API version.
     * Only available to Admin.
     * @param string $packMask
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setServerPackMask($packMask, $multicall = false)
    {
        if (!is_string($packMask)) {
            throw new InvalidArgumentException('packMask = ' . print_r($packMask, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$packMask], $multicall);
    }

    /**
     * Get the packmask of the server.
     * Only available in 2011-08-01 API version.
     * @param bool $multicall
     * @return string
     */
    public function getServerPackMask($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set the mods to apply on the clients.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param bool $override If true, even the maps with a mod will be overridden by the server setting
     * @param Structures\Mod|Structures\Mod[] $mods Array of structures [{string Env, string Url}, ...]
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setForcedMods($override, $mods, $multicall = false)
    {
        if (!is_bool($override)) {
            throw new InvalidArgumentException('override = ' . print_r($override, true));
        }
        if (is_array($mods)) {
            foreach ($mods as $i => &$mod) {
                if (!($mod instanceof Structures\Mod)) {
                    throw new InvalidArgumentException('mods[' . $i . '] = ' . print_r($mod, true));
                }
                $mod = $mod->toArray();
            }
        } elseif ($mods instanceof Structures\Mod) {
            $mods = [$mods->toArray()];
        } else {
            throw new InvalidArgumentException('mods = ' . print_r($mods, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$override, $mods], $multicall);
    }

    /**
     * Get the mods settings.
     * @param bool $multicall
     * @return array {bool Override, Structures\Mod[] Mods}
     */
    public function getForcedMods($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], function ($v) {
                $v['Mods'] = Structures\Mod::fromArrayOfArray($v['Mods']);
                return $v;
            });
        }
        $result = $this->execute(ucfirst(__FUNCTION__));
        $result['Mods'] = Structures\Mod::fromArrayOfArray($result['Mods']);
        return $result;
    }

    /**
     * Set the music to play on the clients.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param bool $override If true, even the maps with a custom music will be overridden by the server setting
     * @param string $music Url or filename relative to the GameData path
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setForcedMusic(bool $override, string $music, $multicall = false)
    {
        if (!preg_match('~^.+?://~', $music)) {
            $music = $this->secureUtf8($music);
        }

        return $this->execute(ucfirst(__FUNCTION__), [$override, $music], $multicall);
    }

    /**
     * Get the music setting.
     * @param bool $multicall
     * @return Structures\Music
     */
    public function getForcedMusic($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('Music'));
        }
        return Structures\Music::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Defines a list of remappings for player skins.
     * Will only affect players connecting after the value is set.
     * Only available to Admin.
     * @param Structures\ForcedSkin|Structures\ForcedSkin[] $skins List of structs {Orig, Name, Checksum, Url}:
     * - Orig is the name of the skin to remap, or '*' for any other
     * - Name, Checksum, Url define the skin to use (you may set value '' for any of those, all 3 null means same as Orig).
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setForcedSkins($skins, $multicall = false)
    {
        if (is_array($skins)) {
            foreach ($skins as $i => &$skin) {
                if (!($skin instanceof Structures\ForcedSkin)) {
                    throw new InvalidArgumentException('skins[' . $i . '] = ' . print_r($skin, true));
                }
                $skin = $skin->toArray();
            }
        } elseif ($skins instanceof Structures\ForcedSkin) {
            $skins = [$skins->toArray()];
        } else {
            throw new InvalidArgumentException('skins = ' . print_r($skins, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$skins], $multicall);
    }

    /**
     * Get the current forced skins.
     * @param bool $multicall
     * @return Structures\ForcedSkin[]
     */
    public function getForcedSkins($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('ForcedSkin', true));
        }
        return Structures\ForcedSkin::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Returns the last error message for an internet connection.
     * Only available to Admin.
     * @param bool $multicall
     * @return string
     */
    public function getLastConnectionErrorMessage($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set a new password for the referee mode.
     * Only available to Admin.
     * @param string $password
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setRefereePassword(string $password, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$password], $multicall);
    }

    /**
     * Get the password for referee mode if called as Admin or Super Admin, else returns if a password is needed or not.
     * @param bool $multicall
     * @return string|bool
     */
    public function getRefereePassword($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set the referee validation mode.
     * Only available to Admin.
     * @param int $mode 0: validate the top3 players, 1: validate all players
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setRefereeMode($mode, $multicall = false)
    {
        if ($mode !== 0 && $mode !== 1) {
            throw new InvalidArgumentException('mode = ' . print_r($mode, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$mode], $multicall);
    }

    /**
     * Get the referee validation mode.
     * @param bool $multicall
     * @return int
     */
    public function getRefereeMode($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set whether the game should use a variable validation seed or not.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param bool $enable
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setUseChangingValidationSeed($enable, $multicall = false)
    {
        if (!is_bool($enable)) {
            throw new InvalidArgumentException('enable = ' . print_r($enable, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$enable], $multicall);
    }

    /**
     * Get the current and next value of UseChangingValidationSeed.
     * @param bool $multicall
     * @return bool[] {bool CurrentValue, bool NextValue}
     */
    public function getUseChangingValidationSeed($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set the maximum time the server must wait for inputs from the clients before dropping data, or '0' for auto-adaptation.
     * Only used by ShootMania.
     * Only available to Admin.
     * @param int $latency
     * @param bool $multicall
     * @return bool
     */
    public function setClientInputsMaxLatency(int $latency, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$latency], $multicall);
    }

    /**
     * Get the current ClientInputsMaxLatency.
     * Only used by ShootMania.
     * @param bool $multicall
     * @return int
     */
    public function getClientInputsMaxLatency($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Sets whether the server is in warm-up phase or not.
     * Only available to Admin.
     * @param bool $enable
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setWarmUp($enable, $multicall = false)
    {
        if (!is_bool($enable)) {
            throw new InvalidArgumentException('enable = ' . print_r($enable, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$enable], $multicall);
    }

    /**
     * Returns whether the server is in warm-up phase.
     * @param bool $multicall
     * @return bool
     */
    public function getWarmUp($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Get the current mode script.
     * @param bool $multicall
     * @return string
     */
    public function getModeScriptText($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set the mode script and restart.
     * Only available to Admin.
     * @param string $script
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setModeScriptText($script, $multicall = false)
    {
        if (!is_string($script)) {
            throw new InvalidArgumentException('script = ' . print_r($script, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$script], $multicall);
    }

    /**
     * Returns the description of the current mode script.
     * @param bool $multicall
     * @return Structures\ScriptInfo
     */
    public function getModeScriptInfo($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('ScriptInfo'));
        }
        return Structures\ScriptInfo::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Returns the current settings of the mode script.
     * @param bool $multicall
     * @return array {mixed <setting name>, ...}
     */
    public function getModeScriptSettings($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Change the settings of the mode script.
     * Only available to Admin.
     * @param mixed[] $settings {mixed <setting name>, ...}
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setModeScriptSettings($settings, $multicall = false)
    {
        if (!is_array($settings) || !$settings) {
            throw new InvalidArgumentException('settings = ' . print_r($settings, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$settings], $multicall);
    }

    /**
     * Send commands to the mode script.
     * Only available to Admin.
     * @param mixed[] $commands {mixed <command name>, ...}
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function sendModeScriptCommands($commands, $multicall = false)
    {
        if (!is_array($commands) || !$commands) {
            throw new InvalidArgumentException('commands = ' . print_r($commands, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$commands], $multicall);
    }

    /**
     * Change the settings and send commands to the mode script.
     * Only available to Admin.
     * @param mixed[] $settings {mixed <setting name>, ...}
     * @param mixed[] $commands {mixed <command name>, ...}
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setModeScriptSettingsAndCommands($settings, $commands, $multicall = false)
    {
        if (!is_array($settings) || !$settings) {
            throw new InvalidArgumentException('settings = ' . print_r($settings, true));
        }
        if (!is_array($commands) || !$commands) {
            throw new InvalidArgumentException('commands = ' . print_r($commands, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$settings, $commands], $multicall);
    }

    /**
     * Returns the current xml-rpc variables of the mode script.
     * @param bool $multicall
     * @return array {mixed <variable name>, ...}
     */
    public function getModeScriptVariables($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set the xml-rpc variables of the mode script.
     * Only available to Admin.
     * @param mixed[] $variables {mixed <variable name>, ...}
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setModeScriptVariables($variables, $multicall = false)
    {
        if (!is_array($variables) || !$variables) {
            throw new InvalidArgumentException('variables = ' . print_r($variables, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$variables], $multicall);
    }

    /**
     * Send an event to the mode script.
     * Only available to Admin.
     * @param string $event
     * @param string|string[] $params
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function triggerModeScriptEvent(string $event, $params = '', $multicall = false)
    {
        if (is_string($params)) {
            return $this->execute(ucfirst(__FUNCTION__), [$event, $params], $multicall);
        }

        if (is_array($params)) {
            foreach ($params as $param) {
                if (!is_string($param)) {
                    throw new InvalidArgumentException('argument must be a string: param = ' . print_r($param, true));
                }
            }
            return $this->execute(ucfirst(__FUNCTION__) . 'Array', [$event, $params], $multicall);
        }

        // else
        throw new InvalidArgumentException('argument must be string or string[]: params = ' . print_r($params, true));
    }

    /**
     * Get the script cloud variables of given object.
     * Only available to Admin.
     * @param string $type
     * @param string $id
     * @param bool $multicall
     * @return array {mixed <variable name>, ...}
     * @throws InvalidArgumentException
     */
    public function getScriptCloudVariables($type, $id, $multicall = false)
    {
        if (!is_string($type)) {
            throw new InvalidArgumentException('type = ' . print_r($type, true));
        }
        if (!is_string($id)) {
            throw new InvalidArgumentException('id = ' . print_r($id, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$type, $id], $multicall);
    }

    /**
     * Set the script cloud variables of given object.
     * Only available to Admin.
     * @param string $type
     * @param string $id
     * @param mixed[] $variables {mixed <variable name>, ...}
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setScriptCloudVariables($type, $id, $variables, $multicall = false)
    {
        if (!is_string($type)) {
            throw new InvalidArgumentException('type = ' . print_r($type, true));
        }
        if (!is_string($id)) {
            throw new InvalidArgumentException('id = ' . print_r($id, true));
        }
        if (!is_array($variables) || !$variables) {
            throw new InvalidArgumentException('variables = ' . print_r($variables, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$type, $id, $variables], $multicall);
    }

    /**
     * Restarts the map.
     * Only available to Admin.
     * @param bool $dontClearCupScores Only available in legacy cup mode
     * @param bool $multicall
     * @return bool
     */
    public function restartMap($dontClearCupScores = false, $multicall = false)
    {
        if (!is_bool($dontClearCupScores)) {
            throw new InvalidArgumentException('dontClearCupScores = ' . print_r($dontClearCupScores, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$dontClearCupScores], $multicall);
    }

    /**
     * Switch to next map.
     * Only available to Admin.
     * @param bool $dontClearCupScores Only available in legacy cup mode
     * @param bool $multicall
     * @return bool
     */
    public function nextMap($dontClearCupScores = false, $multicall = false)
    {
        if (!is_bool($dontClearCupScores)) {
            throw new InvalidArgumentException('dontClearCupScores = ' . print_r($dontClearCupScores, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$dontClearCupScores], $multicall);
    }

    /**
     * Attempt to balance teams.
     * Only available to Admin.
     * @param bool $multicall
     * @return bool
     */
    public function autoTeamBalance($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Stop the server.
     * Only available to SuperAdmin.
     * @param bool $multicall
     * @return bool
     */
    public function stopServer($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * In legacy Rounds or Laps mode, force the end of round without waiting for all players to giveup/finish.
     * Only available to Admin.
     * @param bool $multicall
     * @return bool
     */
    public function forceEndRound($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set new game settings using the struct passed as parameters.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param Structures\GameInfos $gameInfos
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setGameInfos($gameInfos, $multicall = false)
    {
        if (!($gameInfos instanceof Structures\GameInfos)) {
            throw new InvalidArgumentException('gameInfos = ' . print_r($gameInfos, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$gameInfos->toArray()], $multicall);
    }

    /**
     * Returns a struct containing the current game settings.
     * @param bool $multicall
     * @return Structures\GameInfos
     */
    public function getCurrentGameInfo($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('GameInfos'));
        }
        return Structures\GameInfos::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Returns a struct containing the game settings for the next map.
     * @param bool $multicall
     * @return Structures\GameInfos
     */
    public function getNextGameInfo($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('GameInfos'));
        }
        return Structures\GameInfos::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Returns a struct containing two other structures, the first containing the current game settings and the second the game settings for next map.
     * @param bool $multicall
     * @return Structures\GameInfos[] {Structures\GameInfos CurrentGameInfos, Structures\GameInfos NextGameInfos}
     */
    public function getGameInfos($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('GameInfos', true));
        }
        return Structures\GameInfos::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Set a new game mode.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param int $gameMode 0: Script, 1: Rounds, 2: TimeAttack, 3: Team, 4: Laps, 5: Cup, 6: Stunt
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setGameMode($gameMode, $multicall = false)
    {
        if (!is_int($gameMode) || $gameMode < 0 || $gameMode > 6) {
            throw new InvalidArgumentException('gameMode = ' . print_r($gameMode, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$gameMode], $multicall);
    }

    /**
     * Get the current game mode.
     * @param bool $multicall
     * @return int
     */
    public function getGameMode($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set a new chat time value (actually the duration of the podium).
     * Only available to Admin.
     * @param int $chatTime In milliseconds, 0: no podium displayed
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setChatTime($chatTime, $multicall = false)
    {
        if (!is_int($chatTime)) {
            throw new InvalidArgumentException('chatTime = ' . print_r($chatTime, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$chatTime], $multicall);
    }

    /**
     * Get the current and next chat time.
     * @param bool $multicall
     * @return int[] {int CurrentValue, int NextValue}
     */
    public function getChatTime($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set a new finish timeout value for legacy laps and rounds based modes.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param int $timeout In milliseconds, 0: default, 1: adaptative to the map duration
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setFinishTimeout($timeout, $multicall = false)
    {
        if (!is_int($timeout)) {
            throw new InvalidArgumentException('timeout = ' . print_r($timeout, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$timeout], $multicall);
    }

    /**
     * Get the current and next FinishTimeout.
     * @param bool $multicall
     * @return int[] {int CurrentValue, int NextValue}
     */
    public function getFinishTimeout($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set whether to enable the automatic warm-up phase in all modes.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param int $duration 0: disable, number of rounds in rounds based modes, number of times the gold medal time otherwise
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setAllWarmUpDuration($duration, $multicall = false)
    {
        if (!is_int($duration)) {
            throw new InvalidArgumentException('duration = ' . print_r($duration, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$duration], $multicall);
    }

    /**
     * Get whether the automatic warm-up phase is enabled in all modes.
     * @param bool $multicall
     * @return int[] {int CurrentValue, int NextValue}
     */
    public function getAllWarmUpDuration($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set whether to disallow players to respawn.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param bool $disable
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setDisableRespawn($disable, $multicall = false)
    {
        if (!is_bool($disable)) {
            throw new InvalidArgumentException('disable = ' . print_r($disable, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$disable], $multicall);
    }

    /**
     * Get whether players are disallowed to respawn.
     * @param bool $multicall
     * @return bool[] {bool CurrentValue, bool NextValue}
     */
    public function getDisableRespawn($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set whether to override the players preferences and always display all opponents.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param int $opponents 0: no override, 1: show all, else: minimum number of opponents
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setForceShowAllOpponents($opponents, $multicall = false)
    {
        if (!is_int($opponents)) {
            throw new InvalidArgumentException('opponents = ' . print_r($opponents, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$opponents], $multicall);
    }

    /**
     * Get whether players are forced to show all opponents.
     * @param bool $multicall
     * @return int[] {int CurrentValue, int NextValue}
     */
    public function getForceShowAllOpponents($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Set a new mode script name for script mode.
     * Only available to Admin.
     * Requires a map restart to be taken into account.
     * @param string $script
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setScriptName($script, $multicall = false)
    {
        if (!is_string($script)) {
            throw new InvalidArgumentException('script = ' . print_r($script, true));
        }
        $script = $this->secureUtf8($script);

        return $this->execute(ucfirst(__FUNCTION__), [$script], $multicall);
    }

    /**
     * Get the current and next mode script name for script mode.
     * @param bool $multicall
     * @return string[] {string CurrentValue, string NextValue}
     */
    public function getScriptName($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], [$this, 'stripBom']);
        }
        return $this->stripBom($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Returns the current map index in the selection, or -1 if the map is no longer in the selection.
     * @param bool $multicall
     * @return int
     */
    public function getCurrentMapIndex($multicall = false): int
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns the map index in the selection that will be played next (unless the current one is restarted...)
     * @param bool $multicall
     * @return int
     */
    public function getNextMapIndex($multicall = false): int
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Sets the map index in the selection that will be played next (unless the current one is restarted...)
     * @param int $index
     * @param bool $multicall
     * @return bool
     */
    public function setNextMapIndex(int $index, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$index], $multicall);
    }

    /**
     * Sets the map in the selection that will be played next (unless the current one is restarted...)
     * @param string $ident
     * @param bool $multicall
     * @return bool
     */
    public function setNextMapIdent(string $ident, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$ident], $multicall);
    }

    /**
     * Immediately jumps to the map designated by the index in the selection.
     * @param int $index
     * @param bool $multicall
     * @return bool
     */
    public function jumpToMapIndex(int $index, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$index], $multicall);
    }

    /**
     * Immediately jumps to the map designated by its identifier (it must be in the selection).
     * @param string $ident
     * @param bool $multicall
     * @return bool
     */
    public function jumpToMapIdent(string $ident, $multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [$ident], $multicall);
    }

    /**
     * Returns a struct containing the infos for the current map.
     * @param bool $multicall
     * @return Structures\Map
     */
    public function getCurrentMapInfo($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('Map'));
        }
        return Structures\Map::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Returns a struct containing the infos for the next map.
     * @param bool $multicall
     * @return Structures\Map
     */
    public function getNextMapInfo($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('Map'));
        }
        return Structures\Map::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Returns a struct containing the infos for the map with the specified filename.
     * @param string $filename Relative to the Maps path
     * @param bool $multicall
     * @return Structures\Map
     * @throws InvalidArgumentException
     */
    public function getMapInfo(string $filename, $multicall = false)
    {
        $filename = $this->secureUtf8($filename);

        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$filename], $this->structHandler('Map'));
        }
        return Structures\Map::fromArray($this->execute(ucfirst(__FUNCTION__), [$filename]));
    }

    /**
     * Returns a boolean if the map with the specified filename matches the current server settings.
     * @param string $filename Relative to the Maps path
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function checkMapForCurrentServerParams(string $filename, $multicall = false)
    {
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Returns a list of maps among the current selection of the server.
     * @param int $length Maximum number of infos to be returned
     * @param int $offset Starting index in the list
     * @param bool $multicall
     * @return Structures\Map[]
     */
    public function getMapList(int $length = -1, int $offset = 0, $multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$length, $offset], $this->structHandler('Map', true));
        }
        return Structures\Map::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), [$length, $offset]));
    }

    /**
     * Add the map with the specified filename at the end of the current selection.
     * Only available to Admin.
     * @param string $filename Relative to the Maps path
     * @param bool $multicall
     * @return bool
     */
    public function addMap(string $filename, $multicall = false)
    {
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Add the list of maps with the specified filenames at the end of the current selection.
     * Only available to Admin.
     * @param string[] $filenames Relative to the Maps path
     * @param bool $multicall
     * @return int Number of maps actually added
     * @throws InvalidArgumentException
     */
    public function addMapList($filenames, $multicall = false)
    {
        if (!is_array($filenames)) {
            throw new InvalidArgumentException('filenames = ' . print_r($filenames, true));
        }
        $filenames = $this->secureUtf8($filenames);

        return $this->execute(ucfirst(__FUNCTION__), [$filenames], $multicall);
    }

    /**
     * Remove the map with the specified filename from the current selection.
     * Only available to Admin.
     * @param string $filename Relative to the Maps path
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function removeMap($filename, $multicall = false)
    {
        if (!is_string($filename) || !strlen($filename)) {
            throw new InvalidArgumentException('filename = ' . print_r($filename, true));
        }
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Remove the list of maps with the specified filenames from the current selection.
     * Only available to Admin.
     * @param string[] $filenames Relative to the Maps path
     * @param bool $multicall
     * @return int Number of maps actually removed
     * @throws InvalidArgumentException
     */
    public function removeMapList($filenames, $multicall = false)
    {
        if (!is_array($filenames)) {
            throw new InvalidArgumentException('filenames = ' . print_r($filenames, true));
        }
        $filenames = $this->secureUtf8($filenames);

        return $this->execute(ucfirst(__FUNCTION__), [$filenames], $multicall);
    }

    /**
     * Insert the map with the specified filename after the current map.
     * Only available to Admin.
     * @param string $filename Relative to the Maps path
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function insertMap($filename, $multicall = false)
    {
        if (!is_string($filename) || !strlen($filename)) {
            throw new InvalidArgumentException('filename = ' . print_r($filename, true));
        }
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Insert the list of maps with the specified filenames after the current map.
     * Only available to Admin.
     * @param string[] $filenames Relative to the Maps path
     * @param bool $multicall
     * @return int Number of maps actually inserted
     * @throws InvalidArgumentException
     */
    public function insertMapList($filenames, $multicall = false)
    {
        if (!is_array($filenames)) {
            throw new InvalidArgumentException('filenames = ' . print_r($filenames, true));
        }
        $filenames = $this->secureUtf8($filenames);

        return $this->execute(ucfirst(__FUNCTION__), [$filenames], $multicall);
    }

    /**
     * Set as next map the one with the specified filename, if it is present in the selection.
     * Only available to Admin.
     * @param string $filename Relative to the Maps path
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function chooseNextMap($filename, $multicall = false)
    {
        if (!is_string($filename) || !strlen($filename)) {
            throw new InvalidArgumentException('filename = ' . print_r($filename, true));
        }
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Set as next maps the list of maps with the specified filenames, if they are present in the selection.
     * Only available to Admin.
     * @param string[] $filenames Relative to the Maps path
     * @param bool $multicall
     * @return int Number of maps actually chosen
     * @throws InvalidArgumentException
     */
    public function chooseNextMapList($filenames, $multicall = false)
    {
        if (!is_array($filenames)) {
            throw new InvalidArgumentException('filenames = ' . print_r($filenames, true));
        }
        $filenames = $this->secureUtf8($filenames);

        return $this->execute(ucfirst(__FUNCTION__), [$filenames], $multicall);
    }

    /**
     * Set a list of maps defined in the playlist with the specified filename as the current selection of the server, and load the gameinfos from the same file.
     * Only available to Admin.
     * @param string $filename Relative to the Maps path
     * @param bool $multicall
     * @return int Number of maps in the new list
     * @throws InvalidArgumentException
     */
    public function loadMatchSettings($filename, $multicall = false)
    {
        if (!is_string($filename) || !strlen($filename)) {
            throw new InvalidArgumentException('filename = ' . print_r($filename, true));
        }
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Add a list of maps defined in the playlist with the specified filename at the end of the current selection.
     * Only available to Admin.
     * @param string $filename Relative to the Maps path
     * @param bool $multicall
     * @return int Number of maps actually added
     * @throws InvalidArgumentException
     */
    public function appendPlaylistFromMatchSettings($filename, $multicall = false)
    {
        if (!is_string($filename) || !strlen($filename)) {
            throw new InvalidArgumentException('filename = ' . print_r($filename, true));
        }
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Save the current selection of map in the playlist with the specified filename, as well as the current gameinfos.
     * Only available to Admin.
     * @param string $filename Relative to the Maps path
     * @param bool $multicall
     * @return int Number of maps in the saved playlist
     * @throws InvalidArgumentException
     */
    public function saveMatchSettings($filename, $multicall = false)
    {
        if (!is_string($filename) || !strlen($filename)) {
            throw new InvalidArgumentException('filename = ' . print_r($filename, true));
        }
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Insert a list of maps defined in the playlist with the specified filename after the current map.
     * Only available to Admin.
     * @param string $filename Relative to the Maps path
     * @param bool $multicall
     * @return int Number of maps actually inserted
     * @throws InvalidArgumentException
     */
    public function insertPlaylistFromMatchSettings($filename, $multicall = false)
    {
        if (!is_string($filename) || !strlen($filename)) {
            throw new InvalidArgumentException('filename = ' . print_r($filename, true));
        }
        $filename = $this->secureUtf8($filename);

        return $this->execute(ucfirst(__FUNCTION__), [$filename], $multicall);
    }

    /**
     * Returns the list of players on the server.
     * @param int $length Maximum number of infos to be returned
     * @param int $offset Starting index in the list
     * @param int $compatibility 0: united, 1: forever, 2: forever including servers
     * @param bool $multicall
     * @return Structures\PlayerInfo[]
     * @throws InvalidArgumentException
     */
    public function getPlayerList($length = -1, $offset = 0, $compatibility = 1, $multicall = false)
    {
        if (!is_int($length)) {
            throw new InvalidArgumentException('length = ' . print_r($length, true));
        }
        if (!is_int($offset)) {
            throw new InvalidArgumentException('offset = ' . print_r($offset, true));
        }
        if (!is_int($compatibility) || $compatibility < 0 || $compatibility > 2) {
            throw new InvalidArgumentException('compatibility = ' . print_r($compatibility, true));
        }

        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$length, $offset, $compatibility], $this->structHandler('PlayerInfo', true));
        }
        return Structures\PlayerInfo::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), [$length, $offset, $compatibility]));
    }

    /**
     * Returns a struct containing the infos on the player with the specified login.
     * @param mixed $player Login or player object
     * @param int $compatibility 0: united, 1: forever
     * @param bool $multicall
     * @return Structures\PlayerInfo
     * @throws InvalidArgumentException
     */
    public function getPlayerInfo($player, $compatibility = 1, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }
        if ($compatibility !== 0 && $compatibility !== 1) {
            throw new InvalidArgumentException('compatibility = ' . print_r($compatibility, true));
        }

        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$login, $compatibility], $this->structHandler('PlayerInfo'));
        }
        return Structures\PlayerInfo::fromArray($this->execute(ucfirst(__FUNCTION__), [$login, $compatibility]));
    }

    /**
     * Returns a struct containing the infos on the player with the specified login.
     * @param mixed $player Login or player object
     * @param bool $multicall
     * @return Structures\PlayerDetailedInfo
     * @throws InvalidArgumentException
     */
    public function getDetailedPlayerInfo($player, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$login], $this->structHandler('PlayerDetailedInfo'));
        }
        return Structures\PlayerDetailedInfo::fromArray($this->execute(ucfirst(__FUNCTION__), [$login]));
    }

    /**
     * Returns a struct containing the player infos of the game server
     * (ie: in case of a basic server, itself; in case of a relay server, the main server)
     * @param int $compatibility 0: united, 1: forever
     * @param bool $multicall
     * @return Structures\PlayerInfo
     * @throws InvalidArgumentException
     */
    public function getMainServerPlayerInfo($compatibility = 1, $multicall = false)
    {
        if (!is_int($compatibility)) {
            throw new InvalidArgumentException('compatibility = ' . print_r($compatibility, true));
        }

        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$compatibility], $this->structHandler('PlayerInfo'));
        }
        return Structures\PlayerInfo::fromArray($this->execute(ucfirst(__FUNCTION__), [$compatibility]));
    }

    /**
     * Returns the current rankings for the match in progress.
     * In script modes, scores aren't returned.
     * In team modes, the scores for the two teams are returned.
     * In other modes, it's the individual players' scores.
     * @param int $length Maximum number of infos to be returned
     * @param int $offset Starting index in the list
     * @param bool $multicall
     * @return Structures\PlayerRanking[]
     * @throws InvalidArgumentException
     */
    public function getCurrentRanking($length = -1, $offset = 0, $multicall = false)
    {
        if (!is_int($length)) {
            throw new InvalidArgumentException('length = ' . print_r($length, true));
        }
        if (!is_int($offset)) {
            throw new InvalidArgumentException('offset = ' . print_r($offset, true));
        }

        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$length, $offset], $this->structHandler('PlayerRanking', true));
        }
        return Structures\PlayerRanking::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), [$length, $offset]));
    }

    /**
     * Returns the current ranking of the player with the specified login (or list of comma-separated logins) for the match in progress.
     * In script modes, scores aren't returned.
     * In other modes, it's the individual players' scores.
     * @param mixed $players Login, player object or array
     * @param bool $multicall
     * @return Structures\PlayerRanking[]
     * @throws InvalidArgumentException
     */
    public function getCurrentRankingForLogin($players, $multicall = false)
    {
        $logins = $this->getLogins($players);
        if ($logins === false) {
            throw new InvalidArgumentException('players = ' . print_r($players, true));
        }

        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [$logins], $this->structHandler('PlayerRanking', true));
        }
        return Structures\PlayerRanking::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), [$logins]));
    }

    /**
     * Returns the current winning team for the race in progress.
     * @param bool $multicall
     * @return int -1: if not in team mode or draw match, 0 or 1 otherwise
     */
    public function getCurrentWinnerTeam($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Force the scores of the current game.
     * Only available in rounds and team mode.
     * Only available to Admin/SuperAdmin.
     * @param int[][] $scores Array of structs {int PlayerId, int Score}
     * @param bool $silent True to update silently (only available for SuperAdmin)
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function forceScores($scores, $silent, $multicall = false)
    {
        if (!is_array($scores)) {
            throw new InvalidArgumentException('scores = ' . print_r($scores, true));
        }
        foreach ($scores as $i => $score) {
            if (!is_array($score)) {
                throw new InvalidArgumentException('score[' . $i . '] = ' . print_r($score, true));
            }
            if (!isset($score['PlayerId']) || !is_int($score['PlayerId'])) {
                throw new InvalidArgumentException('score[' . $i . ']["PlayerId"] = ' . print_r($score, true));
            }
            if (!isset($score['Score']) || !is_int($score['Score'])) {
                throw new InvalidArgumentException('score[' . $i . ']["Score"] = ' . print_r($score, true));
            }
        }
        if (!is_bool($silent)) {
            throw new InvalidArgumentException('silent = ' . print_r($silent, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$scores, $silent], $multicall);
    }

    /**
     * Force the team of the player.
     * Only available in team mode.
     * Only available to Admin.
     * @param mixed $player Login or player object
     * @param int $team 0 or 1
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function forcePlayerTeam($player, $team, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }
        if ($team !== 0 && $team !== 1) {
            throw new InvalidArgumentException('team = ' . print_r($team, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login, $team], $multicall);
    }

    /**
     * Force the spectating status of the player.
     * Only available to Admin.
     * @param mixed $player Login or player object
     * @param int $mode 0: user selectable, 1: spectator, 2: player, 3: spectator but keep selectable
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function forceSpectator($player, $mode, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }
        if (!is_int($mode) || $mode < 0 || $mode > 3) {
            throw new InvalidArgumentException('mode = ' . print_r($mode, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login, $mode], $multicall);
    }

    /**
     * Force spectators to look at a specific player.
     * Only available to Admin.
     * @param mixed $spectator Login or player object; empty for all
     * @param mixed $target Login or player object; empty for automatic
     * @param int $camera -1: leave unchanged, 0: replay, 1: follow, 2: free
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function forceSpectatorTarget($spectator, $target, $camera, $multicall = false)
    {
        $spectatorLogin = $this->getLogin($spectator, true);
        if ($spectatorLogin === false) {
            throw new InvalidArgumentException('player = ' . print_r($spectator, true));
        }
        $targetLogin = $this->getLogin($target, true);
        if ($targetLogin === false) {
            throw new InvalidArgumentException('target = ' . print_r($target, true));
        }
        if (!is_int($camera) || $camera < -1 || $camera > 2) {
            throw new InvalidArgumentException('camera = ' . print_r($camera, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$spectatorLogin, $targetLogin, $camera], $multicall);
    }

    /**
     * Pass the login of the spectator.
     * A spectator that once was a player keeps his player slot, so that he can go back to player mode.
     * Calling this function frees this slot for another player to connect.
     * Only available to Admin.
     * @param mixed $player Login or player object
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function spectatorReleasePlayerSlot($player, $multicall = false)
    {
        $login = $this->getLogin($player);
        if ($login === false) {
            throw new InvalidArgumentException('player = ' . print_r($player, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$login], $multicall);
    }

    /**
     * Enable control of the game flow: the game will wait for the caller to validate state transitions.
     * Only available to Admin.
     * @param bool $enable
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function manualFlowControlEnable($enable = true, $multicall = false)
    {
        if (!is_bool($enable)) {
            throw new InvalidArgumentException('enable = ' . print_r($enable, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [$enable], $multicall);
    }

    /**
     * Allows the game to proceed.
     * Only available to Admin.
     * @param bool $multicall
     * @return bool
     */
    public function manualFlowControlProceed($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns whether the manual control of the game flow is enabled.
     * Only available to Admin.
     * @param bool $multicall
     * @return int 0: no, 1: yes by the xml-rpc client making the call, 2: yes by some other xml-rpc client
     */
    public function manualFlowControlIsEnabled($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns the transition that is currently blocked, or '' if none.
     * (That's exactly the value last received by the callback.)
     * Only available to Admin.
     * @param bool $multicall
     * @return string
     */
    public function manualFlowControlGetCurTransition($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns the current match ending condition.
     * @param bool $multicall
     * @return string 'Playing', 'ChangeMap' or 'Finished'
     */
    public function checkEndMatchCondition($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Returns a struct containing the networks stats of the server.
     * Only available to SuperAdmin.
     * @param bool $multicall
     * @return Structures\NetworkStats
     */
    public function getNetworkStats($multicall = false)
    {
        if ($multicall) {
            return $this->execute(ucfirst(__FUNCTION__), [], $this->structHandler('NetworkStats'));
        }
        return Structures\NetworkStats::fromArray($this->execute(ucfirst(__FUNCTION__)));
    }

    /**
     * Start a server on lan, using the current configuration.
     * Only available to SuperAdmin.
     * @param bool $multicall
     * @return bool
     */
    public function startServerLan($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Start a server on internet, using the current configuration.
     * Only available to SuperAdmin.
     * @param bool $multicall
     * @return bool
     */
    public function startServerInternet($multicall = false)
    {
        return $this->execute(ucfirst(__FUNCTION__), [], $multicall);
    }

    /**
     * Join the server on lan.
     * Only available on client.
     * Only available to Admin.
     * @param string $host IPv4 with optionally a port (eg. '192.168.1.42:2350')
     * @param string $password
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function joinServerLan($host, $password = '', $multicall = false)
    {
        if (!is_string($host)) {
            throw new InvalidArgumentException('host = ' . print_r($host, true));
        }
        if (!is_string($password)) {
            throw new InvalidArgumentException('password = ' . print_r($password, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [['Server' => $host, 'ServerPassword' => $password]], $multicall);
    }

    /**
     * Join the server on internet.
     * Only available on client.
     * Only available to Admin.
     * @param string $host Server login or IPv4 with optionally a port (eg. '192.168.1.42:2350')
     * @param string $password
     * @param bool $multicall
     * @return bool
     * @throws InvalidArgumentException
     */
    public function joinServerInternet($host, $password = '', $multicall = false)
    {
        if (!is_string($host)) {
            throw new InvalidArgumentException('host = ' . print_r($host, true));
        }
        if (!is_string($password)) {
            throw new InvalidArgumentException('password = ' . print_r($password, true));
        }

        return $this->execute(ucfirst(__FUNCTION__), [['Server' => $host, 'ServerPassword' => $password]], $multicall);
    }
}

/**
 * Exception Dedicated to Invalid Argument Error on Request Call
 */
class InvalidArgumentException extends \Exception
{
}
