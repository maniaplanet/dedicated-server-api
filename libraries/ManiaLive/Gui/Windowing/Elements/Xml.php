<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\Gui\Windowing\Elements;

use ManiaLive\Gui\Toolkit\Manialink;

/**
 * Can be used to add already parsed xml to
 * a manialink window.
 * 
 * @author Florian Schnell
 */
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