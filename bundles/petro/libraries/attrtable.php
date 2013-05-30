<?php
namespace Petro;

class AttrTable
{
	public static function render($data, $columns = null)
	{
		if ( ! isset($data))
		{
			return "No data to display.";
		}

		$model = '';
		$key   = '';

		if (is_null($columns) and $data instanceof \Laravel\Database\Eloquent\Model)
		{
			$model = get_class($data);
			$columns = $model::$properties;
			$key = $model::$key;

			$model .= '.';	// for later use with Lang::line
		}

		$out = static::open($data);

		foreach ($columns as $col => $prop)
		{
			// if the $col is integer value, the actual column name is in its assoc.value
			if (is_int($col))
			{
				$col = $prop;
				$prop = array();	// this is necessary, otherwise, it won't compile!
			}

			// if the $col is marked as not visible, just skip it
			if ($col == $key or (isset($prop['visible']) and $prop['visible'] == false))
			{
				continue;
			}

			// try to determine the label from its name first, unless the user override it later
			// if the Lang::line(lang_key) == false, use Str::title(name) instead
			$label = \Lang::line($model.$col)->get(null, false) ?: \Str::title($col);

			// if label property is defined, use it to override the above
			isset($prop['label']) and $label = $prop['label'];

			$value = '';	// default

			// if $model does not empty, it should be one of Eloquent\Model instance
			if (empty($model))
			{
				$value = $data->$col;
			}
			// if $prop is instanceof Closure, call it
			elseif ($prop instanceof \Closure)
			{
				$value = $prop($data);
			}
			else
			{
				AutoForm::parse($prop);

				if ( isset($prop['options']))
				{
					$value = isset($prop['options'][$data->$col]) ? $prop['options'][$data->$col] : 'ERROR!!';
				}
				else
				{
					$value = isset($data->$col) ? $data->$col : '';
				}
			}

			if (isset($prop['format']))
			{
				$value = Util::format($prop['format'], $value);
			}

			$out .= static::row($label, trim($value));
		}

		$out .= static::close();

		return $out;
	}

	public static function open($data)
	{
		return str_replace(array('{table}', '{id}'),
			array($data->table(), $data->id),
			\Config::get('petro::petro.template.attributes_table.table_open'));
	}

	public static function close()
	{
		return \Config::get('petro::petro.template.attributes_table.table_close');
	}

	public static function row($label, $value)
	{
		return str_replace(array('{label}', '{value}'), array($label, $value),
			\Config::get('petro::petro.template.attributes_table.table_row'));
	}

}