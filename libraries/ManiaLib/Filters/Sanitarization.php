<?php
/**
 * @copyright NADEO (c) 2011
 */

namespace ManiaLib\Filters;

/**
 * Abstraction class of Filter php Module
 * Use the methods to clean correctly variables
 * @author Philippe Melot
 */
abstract class Sanitarization
{
	/**
	 * Remove all characters except letters, digits and !#$%&'*+-/=?^_`{|}~@.[]
	 * from the variables and return them
	 * @param array|string $email
	 * @return array|string the sanitized values
	 */
	static function email($data)
	{
		return self::sanitize($data, FILTER_SANITIZE_EMAIL);
	}

	/**
	 * Remove all characters except digits, +- and optionally .,eE
	 * @param array|string $data
	 * @param bool $allowFraction set to true if you want to allowed . (default = true)
	 * @param bool $allowThousandComma set to true if you want to allow the comma to separate thousand (default = false)
	 * @param bool $allowScientific set to true if you want to allow scientific notation (e or E) (default = false)
	 * @return array|string The Sanitize values
	 */
	static function float($data, $allowFraction = true, $allowThousandComma = false, $allowScientific = true )
	{
		$options = ($allowFraction ? FILTER_FLAG_ALLOW_FRACTION : 0);
		if ($allowThousandComma)
		{
			$options = $options | FILTER_FLAG_ALLOW_THOUSAND;
		}
		
		if ($allowScientific)
		{
			$options = $options | FILTER_FLAG_ALLOW_SCIENTIFIC;
		}
		
		return self::sanitize($data, FILTER_SANITIZE_NUMBER_FLOAT, $options);
	}
	
	/**
	 * Remove all characters except digits, plus and minus sign.
	 * @param array|string $data
	 * @return array|string the sanitized data
	 */
	static function int($data)
	{
		return self::sanitize($data, FILTER_SANITIZE_NUMBER_INT);
	}

	/**
	 * Sanitize the data
	 * @param array|string $data
	 * @param int $filter
	 * @param mixed $options
	 * @return array|string the sanitized data
	 */
	static function sanitize($data, $filter = FILTER_DEFAULT, $options = null)
	{
		if(is_array($data))
		{
			foreach ($data as $key => $value)
			{
				$data[$key] = filter_var($value, $filter, $options);
			}
			return $data;
		}
		else
		{
			return filter_var($data, $filter, $options);
		}
	}
}