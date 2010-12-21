<?php

namespace ManiaLive\Gui\Windowing\Elements;

use ManiaLive\Gui\Toolkit\Manialink;

class Xml extends \ManiaLive\Gui\Windowing\Element
{
	protected $xml;
	
	function __construct($xml = '')
	{
		$this->xml = $xml;
	}
	
	function setContent($xml)
	{
		$this->xml = $xml;
	}
	
	function save()
	{
		$doc = new \DOMDocument();
		$doc->loadXML('<content>' . $this->xml . '</content>');
		foreach ($doc->firstChild->childNodes as $child)
		{
			$node = Manialink::$domDocument->importNode($child, true);
			end(Manialink::$parentNodes)->appendChild($node);
		}
	}
}

?>