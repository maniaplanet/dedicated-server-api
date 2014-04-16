<?php
/**
 * @author NewboO
 */

namespace Maniaplanet\DedicatedServer\Transport;

class GbxRemote
{
	const MAX_REQUEST_SIZE  = 0x200000; // 2MB
	const MAX_RESPONSE_SIZE = 0x400000; // 4MB

	public static $received;
	public static $sent;

	private $socket;
	private $timeouts = array(
		'open' => 5,
		'read' => 1000,
		'write' => 5000
	);
	private $requestHandle = 0x80000000;
	private $callbacksBuffer = array();
	private $multicallBuffer = array();

	/**
	 * @param string $host
	 * @param int $port
	 * @param int[string] $timeouts Override default timeouts for 'open' (in s), 'read' (in ms) and 'write' (in ms) socket operations
	 */
	function __construct($host, $port, $timeouts = array())
	{
		$this->timeouts = array_merge($this->timeouts, $timeouts);
		$this->connect($host, $port);
	}

	/**
	 * @param string $host
	 * @param int $port
	 */
	private function connect($host, $port)
	{
		$this->socket = @fsockopen($host, $port, $errno, $errstr, $this->timeouts['open']);
		stream_set_write_buffer($this->socket, 0);
		if(!$this->socket)
			throw new TransportException('Cannot open socket', TransportException::NOT_INITIALIZED);

		// handshake
		$header = unpack('Vsize', fread($this->socket, 4));
		extract($header);
		if($size != 11)
			throw new TransportException('Wrong protocol header', TransportException::WRONG_PROTOCOL);
		$data = fread($this->socket, $size);
		if($data != 'GBXRemote 2')
			throw new TransportException('Wrong protocol version', TransportException::WRONG_PROTOCOL);
	}

	function terminate()
	{
		if($this->socket)
		{
			fclose($this->socket);
			$this->socket = null;
		}
	}

	function query($method, $args=array())
	{
		$this->assertConnected();
		$xml = XmlRpc::encode($method, $args);

		if(strlen($xml) > self::MAX_REQUEST_SIZE-8)
		{
			if($method != 'system.multicall' || count($args) < 2)
				throw new TransportException('Request too large', TransportException::REQUEST_TOO_LARGE);

			$mid = count($args) >> 1;
			$this->query('system.multicall', array_slice($args, 0, $mid));
			$this->query('system.multicall', array_slice($args, $mid));
		}

		$this->write($xml);
		return $this->flush(true);
	}

	/**
	 * @param string $method
	 * @param mixed[] $args
	 */
	function addCall($method, $args)
	{
		$this->multicallBuffer[] = array(
			'methodName' => $method,
			'params' => $args
		);
	}

	function multiquery()
	{
		switch(count($this->multicallBuffer))
		{
			case 0:
				return;
			case 1:
				$call = array_shift($this->multicallBuffer);
				return $this->query($call['methodName'], $call['params']);
			default:
				$result = $this->query('system.multicall', $this->multicallBuffer);
				$this->multicallBuffer = array();
				return $result;
		}
	}

	/**
	 * @return mixed[]
	 */
	function getCallbacks()
	{
		$this->assertConnected();
		$this->flush();
		$cb = $this->callbacksBuffer;
		$this->callbacksBuffer = array();
		return $cb;
	}

	private function assertConnected()
	{
		if(!$this->socket)
			throw new TransportException('Connection not initialized', TransportException::NOT_INITIALIZED);
	}

	/**
	 * @param bool $waitResponse
	 * @return mixed
	 */
	private function flush($waitResponse=false)
	{
		$n = @stream_select($r=array($this->socket), $w=null, $e=null, 0);
		while($waitResponse || $n > 0)
		{
			list($handle, $xml) = $this->read();
			list($type, $value) = XmlRpc::decode($xml);
			switch($type)
			{
				case 'fault':
					throw new FaultException($value['faultString'], $value['faultCode']);
				case 'response':
					if($handle ^ $this->requestHandle == 0)
						return $value;
					break;
				case 'call':
					$this->callbacksBuffer[] = $value;
			}

			if(!$waitResponse)
				$n = @stream_select($r=array($this->socket), $w=null, $e=null, 0);
		};
	}

	private function read()
	{
		@stream_set_timeout($this->socket, 0, $this->timeouts['read'] * 1000);
		$header = fread($this->socket, 8);
		if($header === '' || $header === false)
			throw new TransportException('Connection interrupted while reading header', TransportException::INTERRUPTED);

		$header = unpack('Vsize/Vhandle', $header);
		extract($header);
		if($size == 0 || $handle == 0)
			throw new TransportException('Incorrect header', TransportException::PROTOCOL_ERROR);

		if($size > self::MAX_RESPONSE_SIZE)
			throw new TransportException('Response too large', TransportException::RESPONSE_TOO_LARGE);

		$data = '';
		while(strlen($data) < $size)
		{
			$buf = fread($this->socket, $size - strlen($data));
			if($buf === '' || $buf === false)
				throw new TransportException('Connection interrupted while reading data', TransportException::INTERRUPTED);
			$data .= $buf;
		}

		self::$received += $size+8;
		return array($handle, $data);
	}

	private function write($data)
	{
		@stream_set_timeout($this->socket, 0, $this->timeouts['write'] * 1000);
		$this->requestHandle = -(~$this->requestHandle);
		$bytes = pack('V2a*', strlen($data), $this->requestHandle, $data);
		while(strlen($bytes) > 0)
		{
			$written = fwrite($this->socket, $bytes);
			if($written === 0 || $written === false)
				throw new TransportException('Connection interrupted while writing', TransportException::INTERRUPTED);

			$bytes = substr($bytes, $written);
		}

		self::$sent += strlen($data)+8;
	}
}

class TransportException extends \Exception
{
	const NOT_INITIALIZED    = 1;
	const INTERRUPTED        = 2;
	const WRONG_PROTOCOL     = 3;
	const PROTOCOL_ERROR     = 4;
	const REQUEST_TOO_LARGE  = 5;
	const RESPONSE_TOO_LARGE = 6;
}

class FaultException extends \Exception {}

?>
