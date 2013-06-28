<?php
namespace Petro;

class Util
{
	// to be used in a2q() and q2a()
	public static $op = array(
		'eq'	    => '=',
		'gt'	    => '>',
		'gte'	    => '>=',
		'lt'	    => '<',
		'lte'	    => '<=',
		'ne'	    => '!=',
		'contains' => 'like',
		'in'	    => 'in',
	);

	public static function lang($key)
	{
		if (is_array($key))
		{
			$key = $key[0].'.'.$key[1];
		}

		return \Lang::line($key)->get(null, false);
	}

	public static function format($format, $value)
	{
		if (is_null($format))
		{
			return $value;
		}

		if ( ! is_array($format))
		{
			$format = static::parse_format($format);
		}

		switch ($format['type'])
		{
			case 'number':
				$p = $format['param'];
				$value = number_format($value, $p[0], $p[1], $p[2]);
				break;
			case 'date':
				$value = static::convert_date($value, $format['from'], $format['to']);
				break;
			default:
				$value = str_replace('{value}', $value, $format['type']);
		}

		return $value;
	}

	protected static function parse_format($format)
	{
		// parse type : param
		$format = explode(':', $format);

		$type = $format[0];
		$param = isset($format[1]) ? explode('|', $format[1]) : array();

		$arr = array();
		$arr['type'] = \Str::lower(trim($type));

		switch ($arr['type'])
		{
			case 'date':
				if (empty($param))
				{
					$arr['from'] = \Config::get('petro::petro.grid.format_date_from');
					$arr['to']   = \Config::get('petro::petro.grid.format_date_to');
				}
				elseif (count($param) == 1)
				{
					$arr['from'] = \Config::get('petro::petro.grid.format_date_from');
					$arr['to']   = $param[0];
				}
				elseif (count($param) == 2)
				{
					$arr['from'] = $param[0];
					$arr['to']   = $param[1];
				}
				break;

			case 'number':
				$def_format = \Config::get('petro::petro.grid.format_number');
				if (empty($param))
				{
					$arr['param'] = $def_format;
				}
				else
				{
					$d  = isset($param[0]) ? $param[0] : $def_format[0];
					$dp = isset($param[1]) ? $param[1] : $def_format[1];
					$ts = isset($param[2]) ? $param[2] : $def_format[2];
					$arr['param'] = array($d, $dp, $ts);
				}
				break;
		}

		return $arr;
	}

	/*
	 * q2a - convert query string to array
	 *
	 * query string must be in the form of
	 *		aa_eq=123&bb_lt=456&cc_gt=789
	 *
	 * will be converted into
	 *		array (
	 *			'aa' = array('=', 123),
	 *			'bb' = array('<', 456),
	 *			'cc' = array('>', 789)
	 *		);
	 *
	 * see, static::$op for support operator
	 */
	public static function q2a($q)
	{
		$arr = array();

		foreach ($q as $k => $v)
		{
			!is_array($v) and $v = trim($v);

			if ( ! empty($v))
			{
				$k = static::parse_query_key($k);

				if (is_array($k))
				{
					if ($k[1] == 'like')
					{
						$v = '%'.$v.'%';
					}
					elseif ($k[1] == 'in')
					{
						$v = \Input::query('q.'.$k[0].'_in');
					}

					$arr[] = array($k[0], $k[1], $v);
				}
				else
				{
					$arr[] = array($k, $v);
				}
			}
		}

		return $arr;
	}

	/**
	 * parse_q - parse string with text operand to actual operand
	 *
	 * from
	 *		aa_contains --> array('aa', 'like')
	 *		bb_eq --> array('bb', '=')
	 *		cc_gte --> array('cc', '>=')
	 *
	 * see, static::$op for support operator
	 */
	private static function parse_query_key($k)
	{
		$a = static::split_last('_', $k);

		if ($a and in_array($a[1], array_keys(static::$op)))
		{
			return array(trim($a[0]), static::$op[$a[1]]);
		}

		return $k;
	}

	public static function split_last($needle, $haystack)
	{
		$pos = strripos($haystack, $needle);
		return $pos ? array(substr($haystack, 0, $pos), substr($haystack, $pos+1)) : false;
	}

/*
	 * a2q - convert array to query string
	 *
	 *		array (
	 *			'aa' = array('=', 123),
	 *			'bb' = array('<', 456),
	 *			'cc' = array('>', 789)
	 *		);
	 *
	 * will be converted to
	 *		aa_eq=123&bb_lt=456&cc_gt=789
	 *
	 * see, static::$op for support operator
	 */
	public static function a2q($a)
	{
		// var_dump($a);
		if ( !is_array($a) )
		{
			throw new Exception('Invalid argument. Expected array parameter in static::a2q()');
		}

		$q = '';

		foreach ($a as $k => $v)
		{
			$q .= empty($q) ? '' : '&';
			if (is_array($v))
			{
				if ($v[1] == 'like')
				{
					$v[2] = str_replace('%', '', $v[2]);
				}
				if (is_array($v[2]))	// handle 'in' operator for checkbox
				{
					$r = 'q['.$v[0].'_'.(static::get_array_key(static::$op, $v[1])).']';
					$s = '';
					foreach ($v[2] as $rk => $rv)
					{
						$s .= empty($s) ?: '&';
						$s .= $r.'['.$rk.']='.$rv;
					}
					$q .= $s;
				}
				else
				{
					$q .= 'q['.$v[0].'_'.(static::get_array_key(static::$op, $v[1])).']='.$v[2];
				}
			}
			else
			{
				$q .= $k.'='.$v;
			}
		}
		// dd($q);
		return $q;
	}

	public static function get_array_key($array, $key)
	{
		foreach ($array as $k => $v)
		{
			if ($v == $key) return $k;
		}

		return false;
	}

	public static function convert_date($input, $from, $to)
	{
		$date = \DateTime::createFromFormat($from, $input);
		return $date->format($to);
	}

	public static function date($year, $month, $day)
	{
		return mktime(0, 0, 0, $month, $day, $year);
	}

	/**
	 * Build array of tables columns.
	 * Adapt from DBUtil.
	 *
	 * @package    DBUtil
	 * @author     Scott Travis <scott.w.travis@gmail.com>
	 * @link       http://github.com/swt83/laravel-dbutil
	 * @license    MIT License
	 *
	 * @param	string	$table
	 * @param	string	$connection
	 *
	 */
	public static function table_columns($table, $connection = null)
	{
		// query the pdo
		$result = \DB::connection($connection)->pdo->query('show columns from '.$table);

		// build array
		$columns = array();
		while ($row = $result->fetch(\PDO::FETCH_NUM))
		{
			$columns[$row[0]] = array(
				'type'    => $row[1],
				'null'    => $row[2],
				'key'     => $row[3],
				'default' => $row[4],
				'extra'   => $row[5]
			);
		}

		// return
		return $columns;
	}
}