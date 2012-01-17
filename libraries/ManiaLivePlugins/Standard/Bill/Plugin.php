<?php
/**
 * Bill Plugin - Allow person to give planets to other persons
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLivePlugins\Standard\Bill;

use ManiaLive\Features\Admin\AdminGroup;
use ManiaLive\Utilities\Validation;

class Plugin extends \ManiaLive\PluginHandler\Plugin
{
    function onInit()
	{
		$this->setVersion('1');
	}

	function onLoad()
	{
		$command = $this->registerChatCommand('pay', 'toServer', 1, true);
		$command->help = 'Give the amount to the server'."\n".
		'sample of usage: /pay 42 will give 42 planets to the server';

		$command = $this->registerChatCommand('pay', 'toPlayer', 2, true);
		$command->help = 'Give the amount to player'."\n".
		'sample of usage: /pay playerLogin 42 will give 42 planets to the player playerLogin';

		$command = $this->registerChatCommand('give', 'fromServer', 2, true, AdminGroup::get());
		$command->help = 'Give the amount to the player'."\n".
		'sample of usage: /give playerLogin 42 will give 42 planets to the player playerLogin with the planets of the server account';

		$command = $this->registerChatCommand('checkBill', 'checkBill', 1, true);
		$command->help = 'Give the current state of the bill'."\n".
		'sample of usage: /checkBill 42 where 42 is the id of the bill';

		$this->setPublicMethod('payFromServer');
		$this->setPublicMethod('payToServer');
	}

	function payFromServer($pluginId, $payee, $amount, $label)
	{
		$this->fromServer('', $payee, $amount, $label);
	}

	function payToServer($pluginId, $playerLogin, $amount, $label)
	{
		$this->connection->chatSendServerMessage('The plugin '.$pluginId.' has created a bill for you to the server of '.$amount.' planets');
		$this->toServer($playerLogin, $amount, $label);
	}

	function fromServer($login, $payee, $amount, $label = '')
	{
		try
		{
			Validation::int($amount, 0);
		}
		catch(\Exception $e)
		{
			$this->connection->chatSendServerMessage('$F00The amount is incorrect', $login, true);
			return;
		}
		$label = $label ? $label : $this->storage->serverLogin.'To'.$payee;
		$billId = $this->connection->pay($payee, (int)$amount, $label);
		$this->connection->chatSendServerMessage('The bill has been created with the id '.$billId, $login, true);
	}

	function toServer($fromLogin, $amount, $label = '')
	{
		try
		{
			Validation::int($amount, 0);
		}
		catch(\Exception $e)
		{
			$this->connection->chatSendServerMessage('$F00The amount is incorrect', $fromLogin, true);
			return;
		}

		$label = $label ? $label : $fromLogin.'ToServer'.$this->storage->serverLogin;

		$billId = $this->connection->sendBill($fromLogin, (int)$amount, $label);
		$this->connection->chatSendServerMessage('The bill has been created with the id '.$billId, $fromLogin, true);
	}

	function toPlayer($payerLogin, $payeeLogin, $amount)
	{
		if($payerLogin == $payeeLogin)
		{
			$this->connection->chatSendServerMessage('$F00You can\'t give planets to yourself', $payerLogin, true);
			return;
		}

		try
		{
			Validation::int($amount, 0);
		}
		catch(\Exception $e)
		{
			$this->connection->chatSendServerMessage('$F00The amount is incorrect', $payerLogin, true);
			return;
		}

		$billId = $this->connection->sendBill($payerLogin, (int)$amount, $payerLogin.'To'.$payeeLogin, $payeeLogin);
		$this->connection->chatSendServerMessage('The bill has been created with the id '.$billId, $payerLogin, true);
	}

	function checkBill($login, $billId)
	{
		$bill = $this->connection->getBillState((int)$billId);

		$this->connection->chatSendServerMessage('The bill n°'.$billId.' is currently $<$o'.$bill->stateName.'$>', $login, true);
	}
}
?>