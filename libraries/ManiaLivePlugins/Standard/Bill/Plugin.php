<?php
/**
 * Transaction Plugin - Allow person to give coppers to other persons
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace ManiaLivePlugins\Standard\Bill;

use ManiaLive\Features\Admin\AdminGroup;

use ManiaLib\Filters\Validation;

class Plugin extends \ManiaLive\PluginHandler\Plugin
{

    function onInit()
	{
		$this->setVersion(1.0);
	}

	function onLoad()
	{
		$command = $this->registerChatCommand('pay', 'toServer', 1, true);
		$command->help = 'Give the amount to the server'."\n".
		'sample of usage: /pay 42 will give 42 coppers to the server';

		$command = $this->registerChatCommand('pay', 'toPlayer', 2, true);
		$command->help = 'Give the amount to player'."\n".
		'sample of usage: /pay playerLogin 42 will give 42 coppers to the player playerLogin';

		$command = $this->registerChatCommand('give', 'fromServer', 2, true, AdminGroup::get());
		$command->help = 'Give the amount to the player'."\n".
		'sample of usage: /give playerLogin 42 will give 42 coppers to the player playerLogin with the coppers of the server account';

		$command = $this->registerChatCommand('checkBill', 'checkBill', 1, true);
		$command->help = 'Give the current state of the bill'."\n".
		'sample of usage: /give 42 where 42 is the id of the bill';

		$this->setPublicMethod('payFromServer');
		$this->setPublicMethod('payToServer');
	}

	function payFromServer($pluginId, $payee, $amount, $label)
	{
		$this->fromServer('', $payee, $amount, $label);
	}

	function fromServer($login, $payee, $amount, $label = '')
	{
		Validation::int($amount,0);
		$label = $label ? $label : $this->storage->serverLogin.'To'.$payee;
		$billId = $this->connection->sendBill($this->storage->serverLogin, $amount, $label, $payee);
		$player = ($login ? $this->storage->getPlayerObject($login) : AdminGroup::get());
		$this->connection->chatSendServerMessage('The bill has been created with the id '.$billId, $player, true);
	}

	function payToServer($pluginId, $playerLogin, $amount, $label)
	{
		$this->connection->chatSendServerMessage('The plugin '.$pluginId.' has created a bill for you to the server of '.$amount.' coppers');
		$this->toServer($playerLogin, $amount, $label);
	}

	function toServer($fromLogin, $amount, $label = '')
	{
		$player = $this->storage->getPlayerObject($fromLogin);
		if(!$player)
		{
			return;
		}

		try
		{
			Validation::int($amount,0);
		}
		catch(\Exception $e)
		{
				$this->connection->chatSendServerMessage('$F00The amount is incorrect', $player, true);
				return;
		}

		$label = $label ? $label : $player->login.'ToServer'.$this->storage->serverLogin;

		$billId = $this->connection->sendBill($player->login, $amount, $label);
		$this->connection->chatSendServerMessage('The bill has been created with the id '.$billId, $player, true);
	}

	function toPlayer($payerLogin, $payeeLogin, $amount)
	{
		if($payerLogin == $payeeLogin)
		{
			$this->connection->chatSendServerMessage('$F00You can\'t give coppers to yourself', $player, true);
			return;
		}

		$payer = $this->storage->getPlayerObject($payerLogin);

		if(!$player)
		{
			return;
		}

		try
		{
			Validation::int($amount,0);
		}
		catch(\Exception $e)
		{
				$this->connection->chatSendServerMessage('$F00The amount is incorrect', $player, true);
				return;
		}

		$billId = $this->connection->sendBill($payer->login, $amount, $payer->login.'To'.$payeeLogin, $payeeLogin);
		$this->connection->chatSendServerMessage('The bill has been created with the id '.$billId, $player, true);
	}

	function checkBill($login, $billId)
	{
		$bill = $this->connection->getBillState($billId);

		$this->connection->chatSendServerMessage('The bill n°'.$billId.' is currently $<$o'.$bill->stateName.'$>', $this->storage->getPlayerObject($login),true);
	}
}
?>