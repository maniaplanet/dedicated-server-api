<?php
/**
 * @copyright NADEO (c) 2010
 */

namespace ManiaLive\DedicatedApi\Xmlrpc;

class Message 
{
	public $message;
	public $messageType;  // methodCall / methodResponse / fault
	public $faultCode;
	public $faultString;
	public $methodName;
	public $params;
	// Current variable stacks
	protected $_arraystructs = array();  // Stack to keep track of the current array/struct
	protected $_arraystructstypes = array();  // Stack to keep track of whether things are structs or array
	protected $_currentStructName = array();  // A stack as well
	protected $_param;
	protected $_value;
	protected $_currentTag;
	protected $_currentTagContents;
	// The XML parser
	protected $_parser;

	function __construct ($message) 
	{
		$this->message = $message;
	}

	function parse() 
	{
		
		// first remove the XML declaration
		$this->message = preg_replace('/<\?xml(.*)?\?'.'>/', '', $this->message);
		if (trim($this->message) == '') 
		{
			return false;
		}
		$this->_parser = xml_parser_create();
		// Set XML parser to take the case of tags into account
		xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
		// Set XML parser callback functions
		xml_set_object($this->_parser, $this);
		xml_set_element_handler($this->_parser, 'tag_open', 'tag_close');
		xml_set_character_data_handler($this->_parser, 'cdata');
		if (!xml_parse($this->_parser, $this->message)) 
		{
			/* die(sprintf('GbxRemote XML error: %s at line %d',
			               xml_error_string(xml_get_error_code($this->_parser)),
			               xml_get_current_line_number($this->_parser))); */
			return false;
		}
		xml_parser_free($this->_parser);
		// Grab the error messages, if any
		if ($this->messageType == 'fault') 
		{
			$this->faultCode = $this->params[0]['faultCode'];
			$this->faultString = $this->params[0]['faultString'];
		}
		return true;
	}

	function tag_open($parser, $tag, $attr) 
	{
		$this->currentTag = $tag;
		switch ($tag) 
		{
			case 'methodCall':
			case 'methodResponse':
			case 'fault':
				$this->messageType = $tag;
				break;
			// Deal with stacks of arrays and structs
			case 'data':  // data is to all intents and purposes more interesting than array
				$this->_arraystructstypes[] = 'array';
				$this->_arraystructs[] = array();
				break;
			case 'struct':
				$this->_arraystructstypes[] = 'struct';
				$this->_arraystructs[] = array();
				break;
		}
	}

	function cdata($parser, $cdata) 
	{
		$this->_currentTagContents .= $cdata;
	}

	function tag_close($parser, $tag) 
	{
		$valueFlag = false;
		switch ($tag) 
		{
			case 'int':
			case 'i4':
				$value = (int)trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'double':
				$value = (double)trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'string':
				$value = (string)trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'dateTime.iso8601':
				$value = new Date(trim($this->_currentTagContents));
				// $value = $iso->getTimestamp();
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'value':
				// If no type is indicated, the type is string
				if (trim($this->_currentTagContents) != '') {
					$value = (string)$this->_currentTagContents;
					$this->_currentTagContents = '';
					$valueFlag = true;
				}
				break;
			case 'boolean':
				$value = (boolean)trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'base64':
				$value = base64_decode($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
				// Deal with stacks of arrays and structs
			case 'data':
			case 'struct':
				$value = array_pop($this->_arraystructs);
				array_pop($this->_arraystructstypes);
				$valueFlag = true;
				break;
			case 'member':
				array_pop($this->_currentStructName);
				break;
			case 'name':
				$this->_currentStructName[] = trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				break;
			case 'methodName':
				$this->methodName = trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				break;
		}

		if ($valueFlag) 
		{
			/*
			if (!is_array($value) && !is_object($value)) {
				$value = trim($value);
			}
			*/
			if (count($this->_arraystructs) > 0) 
			{
				// Add value to struct or array
				if ($this->_arraystructstypes[count($this->_arraystructstypes)-1] == 'struct') 
				{
					// Add to struct
					$this->_arraystructs[count($this->_arraystructs)-1][$this->_currentStructName[count($this->_currentStructName)-1]] = $value;
				} 
				else 
				{
					// Add to array
					$this->_arraystructs[count($this->_arraystructs)-1][] = $value;
				}
			} 
			else 
			{
				// Just add as a paramater
				$this->params[] = $value;
			}
		}
	}
}

?>