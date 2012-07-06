<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 7580 $:
 * @author      $Author: gwendal $:
 * @date        $Date: 2012-06-27 11:56:13 +0200 (mer., 27 juin 2012) $:
 */

namespace ManiaLivePlugins\Standard\TrustCircle;

class Rule
{
	private $toEval;
	private $type;
	
	static function Prepare($raw)
	{
		$parts = explode('=>', $raw);
		if(count($parts) != 2)
			throw new \Exception('Invalid rule format');
		
		list($toEval, $type) = $parts;
		
		return new self(
				str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B"), '', $toEval),
				str_replace(array(" ", "\t", "\n", "\r", "\0", "\x0B"), '', $type)
			);
	}
	
	private function __construct($toEval, $type)
	{
		switch($type)
		{
			case 'W': $this->type = 2; break;
			case 'Y': $this->type = 1; break;
			case 'N': $this->type = -1; break;
			case 'B': $this->type = -2; break;
			default: throw new \Exception('Invalid rule type');
		}
		
		$this->toEval = 'return '.str_replace(array('B', 'W'), array('$blacks', '$whites'), $toEval).';';
		try
		{
			$blacks = $whites = 0;
			eval($this->toEval);
		}
		catch(\Exception $e)
		{
			throw new \Exception('Invalid rule eval string: '.$e->getMessage());
		}
	}
	
	function check($blacks, $whites)
	{
		if(eval($this->toEval))
			return $this->type;
		return 0;
	}
}

?>
