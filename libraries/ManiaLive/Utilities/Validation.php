<?php
/**
 * @copyright NADEO (c) 2011
 */

namespace ManiaLive\Utilities;

// FIXME When throwing exception in Validation, get the last element of the stack trace to kown where it comes from

abstract class Validation
{
	/**
	 * Check if the data is a boolean
	 * Returns TRUE for "1", "true", "on" and "yes".
	 * FALSE is returned only for "0", "false", "off", "no", and "".
	 * NULL is returned for all non-boolean values.
	 * @param mixed $data
	 * @return array[bool]|bool
	 */
	static function bool($data)
	{
		self::validate($data, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	}
	
	/**
	 * Validates value as e-mail.
	 * @param array|mixed $data
	 * @return array[bool]|bool
	 */
	static function email($data)
	{
		self::validate($data, FILTER_VALIDATE_EMAIL);
	}
	
	/**
	 * Validates value as float.
	 * @param array|mixed $data
	 * @return array[bool]|bool
	 */
	static function float($data)
	{
		self::validate($data, FILTER_VALIDATE_FLOAT);
	}
	
	/**
	 * Validates value as an integer.
	 * @param array|mixed $data
	 * @param int $minRange optionnal parameter to set a minimal value
	 * @param int $maxRange optionnal parameter to set a maximal value
	 * @param bool optionnal parameter to define if octal values are valid
	 * @param bool optionnal parameter to define if hexadecimal values are valid
	 * @return array[bool]|bool
	 */
	static function int($data, $minRange = null, $maxRange = null, $allowOctal = false, $allowHexa = false)
	{
		$options = array();
		if($minRange !== null)
		{
			$options['options'] = array();
			$options['options']['min_range'] = (int)$minRange;
		}
		
		if($maxRange !== null)
		{
			if(!isset($options['options']))
			{
				$options['options'] = array();
			}
			$options['options']['max_range'] = (int)$maxRange;
		}
		
		$options['flags'] = ($allowOctal ? FILTER_FLAG_ALLOW_OCTAL : 0 );
		
		if($allowHexa)
		{
			$options['flags'] = $options['flags'] | FILTER_FLAG_ALLOW_HEX;
		}
		
		if(!count($options))
		{
			$options = null;
		}
		
		self::validate($data, FILTER_VALIDATE_INT, $options);
	}
	
	/**
	 * Validates value as IP address, optionally only IPv4 or IPv6 or not from private or reserved ranges.
	 * @param array|mixed $data
	 * @return array[bool]|bool
	 */
	static function ip($data, $allowIpv4 = true, $allowIpv6 = true, $allowPrivate = true, $allowReserved = false)
	{
		$options = ($allowIpv4 ? FILTER_FLAG_IPV4 : ~FILTER_FLAG_IPV4);
		
		if ($allowIpv6)
		{
			$options = $options & FILTER_FLAG_IPV6;
		}
		
		if (!$allowPrivate)
		{
			$options = $options | FILTER_FLAG_NO_PRIV_RANGE;
		}
		
		if (!$allowReserved)
		{
			$options = $options | FILTER_FLAG_NO_RES_RANGE;
		}
		
		self::validate($data, FILTER_VALIDATE_IP, $options);
	}
	
	/**
	 * Validates data against regexp, a Perl-compatible regular expression.
	 * @param array|mixed $data
	 * @return array[bool]|bool
	 */
	static function regularExpression($data)
	{
		self::validate($data, FILTER_VALIDATE_REGEXP);
	}
	
	/**
	 * Validates value as URL (according to » http://www.faqs.org/rfcs/rfc2396), 
	 * optionally with required components. Note that the function will only find 
	 * ASCII URLs to be valid; internationalized domain names 
	 * (containing non-ASCII characters) will fail.
	 * @param array|mixed $data
	 * @return array[bool]|bool
	 */
	static function url($data, $requirePath = false, $requireQuery = false)
	{
		$options = ($requirePath ? FILTER_FLAG_PATH_REQUIRED : 0);
		
		if ($requireQuery)
		{
			$options = $options | FILTER_FLAG_QUERY_REQUIRED;
		}
		
		self::validate($data, FILTER_VALIDATE_URL, $options);
	}
	
	/**
	 * validate data
	 * @param array|string $data
	 * @param int $filter
	 * @param mixed $options
	 * @return array|string the sanitized data
	 */
	static function validate($data, $filter = FILTER_DEFAULT, $options = null)
	{
		if(is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if(filter_var($value, $filter, $options) === false)
				{
					throw new \InvalidArgumentException();
				}
			}
		}
		else
		{
			if(filter_var($data, $filter, $options) === false)
			{
				throw new \InvalidArgumentException();
			}
		}
	}
}