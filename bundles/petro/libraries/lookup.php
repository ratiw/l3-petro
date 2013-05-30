<?php
namespace Petro;

use \DB;

class Lookup
{
	protected static $config = null;

	public static function _init()
	{
		static::$config = \Config::get('petro::petro.lookup');
	}

	public static function get($type, $item = null, $domain = null, $key_col = null, $value_col = null)
	{
		isset($domain)  or $domain = static::$config['table'];
		isset($key_col) or $key_col = static::$config['key_column'];
		isset($value_col) or $value_col = static::$config['value_column'];

		$type = isset($type) ? strtoupper(trim($type)) : null;

		$query = static::setup_query($type, $domain);

		if (isset($item))
		{

			$value = $query->where($key_col, $item)->get($value_col);
			return \Lang::line($value)->get(null, false) ?: $value;
		}
		else
		{
			$result = $query->get(array($key_col, $value_col));
			return static::_to_array($result, $key_col, $value_col);
		}
	}

	public static function table($domain, $key_col, $value_col, $item = null)
	{
		return static::get(null, $item, $domain, $key_col, $value_col);
	}

	private static function setup_query($type, $domain, $sort = null, $direction = 'asc')
	{
		$query = DB::table($domain);

		isset($type) and $query->where('type', '=', $type);

		if (isset($sort))
		{
			$query->order_by($sort, $direction);
		}
		elseif ($domain == static::$config['table'])
		{
			$query->order_by(static::$config['sort_column'], $direction);
		}

		return $query;
	}

	public static function _to_array($result, $key_col, $value_col)
	{
		$arr = array();

		foreach ($result as $item)
		{
			$value = $item->$value_col;
			$arr[$item->$key_col] = \Lang::line($value)->get(null, false) ?: $value;
		}

		return $arr;
	}
}