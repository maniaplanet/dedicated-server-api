<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\DedicatedApi\Structures;

class ScriptInfo extends AbstractStructure
{

	public $name;
	public $compatibleMapTypes;
	public $description;
	public $version
	public $paramDescs = array();

	static public function fromArray($array)
	{
		$object = parent::fromArray($array);

		if($object->paramDescs)
		{
			$object->paramDescs = ScriptSettings::fromArrayOfArray($object->paramDescs);
		}
		return $object;
	}

}

?>