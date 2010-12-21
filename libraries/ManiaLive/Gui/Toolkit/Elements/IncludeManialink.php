<?php

namespace ManiaLive\Gui\Toolkit\Elements;

/**
 * Include
 * Manialink include tag, used to include another Manialink file inside a Manialink
 */
class IncludeManialink extends Element
{
	function __construct()
	{
	}

	protected $xmlTagName = 'include';
	protected $halign = null;
	protected $valign = null;
	protected $posX = null;
	protected $posY = null;
	protected $posZ = null;
}

?>