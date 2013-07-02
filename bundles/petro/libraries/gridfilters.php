<?php
namespace Petro;

class GridFilters
{

	/**
	 * [render_group description]
	 * @param  [type] $model         [description]
	 * @param  [type] $group_filters array of array(<group_label>, array())
	 * @return [type]                [description]
	 */
	public static function render_group($model, $group_filters)
	{

		$out = \Form::open(\URI::current(), 'GET', array('id' => 'q_search', 'class' => 'form-vertical form-filter'));

		$i = 1;
		foreach ($group_filters as $g)
		{
			$group_id = 'filter-group-'.$i;
			$out .= '<a class="filter-group-label" data-toggle="collapse" data-target="#'.$group_id.'">';
			$out .= $g[0];	// group label
			$out .= '</a>';
			$out .= '<div id="'.$group_id.'" class="filter-group collapse'.($i == 1 ? ' in' : '').'">';
			$out .= static::make_filters($model, $filters);
			$out .= '</div>';
			$i++;
		}
		$out .= static::make_actions();
		$out .= \Form::close();

		return $out;
	}

	public static function render($model, $filters)
	{
		if ( ! class_exists($model))
		{
			throw new \Exception('Petro : The given model "'.$model.'" does not exist.');
		}

		$out = '';

		$out .= \Form::open(\URI::current(), 'GET', array('id' => 'q_search', 'class' => 'form-vertical form-filter'));
		$out .= static::make_filters($model, $filters);
		$out .= static::make_actions();
		$out .= \Form::close();

		return $out;
	}

	public static function make_filters($model, $filters)
	{
		$param = \Input::query('q', array());
		$out = '';

		foreach ($filters as $name => &$prop)
		{
			if (is_int($name))
			{
				$name = $prop;
				$prop = array('type' => 'string');
			}
			$label = isset($prop['label'])
				? $prop['label']
				: \Lang::line($model.'.'.$name)->get(null, false) ?: \Str::title($name);

			$out .= static::make_control($name, $label, $prop, $param);
		}

		return $out;
	}

	public static function make_actions()
	{
		$out = '<div class="filter-buttons">'.PHP_EOL;
		$out .= 	'<button class="btn btn-primary" id="q_submit" name="commit" type="submit">Filter</button>'.PHP_EOL;
		$out .= 	'<button class="btn clear_filters_btn" type="reset">Clear Filters</button>'.PHP_EOL;
		$out .= '</div>'.PHP_EOL;
		return $out;
	}

	public static function make_control($name, $label, $prop, $param)
	{
		$type = \Str::lower($prop['type']);
		$collection = isset($prop['collection']) ? $prop['collection'] : array();

		$out = '<div class="filter-'.$type.'">';

		switch ($type) {
			case 'date':
				$out .= static::filter_date($name, $label, $param);
				break;
			case 'date_range':
				$out .= static::filter_date_range($name, $label, $param);
				break;
			case 'numeric':
				$out .= static::filter_numeric($name, $label, $param);
				break;
			case 'select':
				$out .= static::filter_select($name, $label, $collection, $param);
				break;
			case 'checkbox':
				$out .= static::filter_checkbox($name, $label, $collection, $param);
				break;
			case 'radio':
				$out .= static::filter_radio($name, $label, $collection, $param);
				break;
			default:
				$out .= static::filter_string($name, $label, $param);
		}
		$out .= '</div>';

		return $out;
	}

	protected static function label($name, $label)
	{
		return '<label for="q_'.$name.'">'.$label.'</label>';
	}

	protected static function filter_string($name, $label, $param)
	{
		$op = '_contains';
		$value = array_key_exists($name.$op, $param) ? $param[$name.$op] : '';

		$out = static::label($name.$op, $label);
		$out .= '<input id="q_'.$name.$op.'" name="q['.$name.$op.']" type="text" value="'.$value.'">';
		return $out;
	}

	protected static function filter_date($name, $label, $param)
	{
		$op = '_eq';
		$value = array_key_exists($name.$op, $param) ? $param[$name.$op] : '';

		$out = static::label($name.$op, $label);
		$out .= '<input class="datepicker" id="q_'.$name.$op.'" max="10" name="q['.$name.$op.']" size="12" type="text" value="'.$value.'">';
		return $out;
	}

	protected static function filter_date_range($name, $label, $param)
	{
		$gte = '_gte';
		$lte = '_lte';
		$value1 = array_key_exists($name.$gte, $param) ? $param[$name.$gte] : '';
		$value2 = array_key_exists($name.$lte, $param) ? $param[$name.$lte] : '';

		$out = static::label($name.$gte, $label);
		$out .= '<input class="datepicker" id="q_'.$name.$gte.'" max="10" name="q['.$name.$gte.']" size="12" type="text" value="'.$value1.'">'.PHP_EOL;
		$out .= '<span class="separator">-</span>'.PHP_EOL;
		$out .= '<input class="datepicker" id="q_'.$name.$lte.'" max="10" name="q['.$name.$lte.']" size="12" type="text" value="'.$value2.'">'.PHP_EOL;
		return $out;
	}

	protected static function filter_numeric($name, $label, $param)
	{
		$op = '_eq';
		$value = array_key_exists($name.$op, $param) ? $param[$name.$op] : '';

		$out = static::label($name.'_numeric', $label);
		$out .= '<select onchange="'."document.getElementById('".$name."').name = 'q[' + this.value + ']';".'">'.PHP_EOL;
		$out .= 	'<option value="'.$name.'_eq" selected="selected">Equal To</option>'.PHP_EOL;
		$out .= 	'<option value="'.$name.'_gt">Greater Than</option>'.PHP_EOL;
		$out .= 	'<option value="'.$name.'_lt">Less Than</option>'.PHP_EOL;
		$out .= '</select>'.PHP_EOL;
		$out .= '<input id="'.$name.'_numeric" name="q['.$name.'_eq]" size="10" type="text" value="'.$value.'">'.PHP_EOL;
		return $out;
	}

	protected static function filter_select($name, $label, $collection, $param)
	{
		$op = '_eq';

		$out = static::label($name.$op, $label);

		$out .= '<select id="q_'.$name.$op.'" name="q['.$name.$op.']">'.PHP_EOL;
		$out .= '<option value=""></option>'.PHP_EOL;

		foreach ($collection as $k => $v)
		{
			$selected = (array_key_exists($name.$op, $param) and $param[$name.$op] == $k) ? ' selected="selected"' : '';
			$out .= '<option value="'.$k.'"'.$selected.'>'.__($v).'</option>'.PHP_EOL;
		}

		$out .= '</select>'.PHP_EOL;
		return $out;
	}

	protected static function filter_checkbox($name, $label, $collection, $param)
	{
		$op = '_in';

		$out = static::label($name, $label);
		$out .= '<div class="checkbox_wrapper">'.PHP_EOL;
		foreach ($collection as $k => $v)
		{
			$checked = isset($param[$name.$op][$k]) ? ' checked="checked"' : '';
			$out .= '<label for="q_'.$name.'_in_'.$k.'">';
			$out .= '<input type="checkbox" id="q_'.$name.'_in_'.$k.'" name="q['.$name.$op.']['.$k.']" value="'.$k.'"'.$checked.'>';
			$out .= '&nbsp;&nbsp;'.$v.'</label>'.PHP_EOL;
		}
		$out .= '</div>'.PHP_EOL;
		return $out;
	}

	protected static function filter_radio($name, $label, $collection, $param)
	{
		$op = '_in';
		$value = array_key_exists($name.$op, $param) ? $param[$name.$op] : '';

		$out = static::label($name, $label);
		$out .= '<div class="radio_wrapper">'.PHP_EOL;
		foreach ($collection as $k => $v)
		{
			$checked = (!empty($value) and $value == $k) ? ' checked="checked"' : '';
			$out .= '<label for="q_'.$name.'_in_'.$k.'">';
			$out .= '<input type="radio" id="q_'.$name.'_in_'.$k.'" name="q['.$name.$op.']" value="'.$k.'"'.$checked.'>';
			$out .= '&nbsp;&nbsp;'.__($v).'</label>'.PHP_EOL;
		}
		$out .= '</div>'.PHP_EOL;
		return $out;
	}

}