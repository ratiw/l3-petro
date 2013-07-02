<?php

namespace Petro;

class AutoForm
{
	protected static $template = 'petro::petro.template.';

	protected $model = null;

	protected $attributes = array();

	protected $fields = array();

	protected $rules = null;

	protected $buttons = array();

	// protected $validation = null;

	protected $sequence = null;


	public static function set_template($template)
	{
		if ( ! empty($template))
		{
			static::$template = $template;
		}
	}

	public function __construct($form_attr = array())
	{
		$form_style = \Config::get('petro::petro.form.style');

		if (empty($form_attr))
		{
			$this->attributes['class'] = $form_style;
		}
		else
		{
			if ( ! isset($form_attr['class']))
			{
				$form_attr['class'] = $form_style;
			}
			$this->attributes = array_merge($this->attributes, $form_attr);
		}
	}

	public function add_model($model)
	{
		$this->model = $model;

		$this->rules = isset($model::$rules) ? $model::$rules : array();
		$this->fields = static::grab_fields($model);
	}

	public static function grab_fields($model)
	{
		if ( ! class_exists($model))
		{
			throw new \Exception('Petro : The given model "'.$model.'" does not exist.');
		}

		$fields = array();

		$uneditables = isset($model::$uneditables) ? $model::$uneditables : array();

		if (property_exists($model, 'properties'))
		{
			$properties = $model::$properties;
		}
		else
		{
			$m = new $model;
			$properties = array_keys(Util::table_columns($m->table()));
		}

		// foreach ($model::$properties as $name => $settings)
		foreach ($properties as $name => $settings)
		{
			if (is_int($name))
			{
				$name = $settings;
				$settings = array();
			}

			// if the field is primary key, we make it invisible and uneditable
			if ($name == $model::$key)
			{
				$settings['visible'] = $settings['editable'] = false;
			}
			// if the field is in uneditables list, make it uneditable during edit
			if (in_array($name, $uneditables))
			{
				$settings['editable'] = false;
			}
			// if the label is not set, we try to find one from the language file or its name
			if ( ! isset($settings['label']))
			{
				$settings['label'] = Util::lang(array($model, $name)) ?: str_replace('_', ' ', \Str::title($name));
			}

			$fields[$name] = static::add_field($name, '', $settings);
		}

		return $fields;
	}

	public static function add_field($name, $value = '', $settings = array())
	{
		static::parse($settings);

		return array(
			'name'     => $name,
			'value'    => $value,
			'settings' => $settings
		);
	}

	public static function parse(&$settings)
	{
		// invalid format, just return empty array
		if (!is_array($settings)) return array();

		// lookup attribute
		if (isset($settings['type']) and \Str::lower($settings['type']) == 'select' and isset($settings['lookup']))
		{
			$source = $settings['lookup'];
			if (is_array($source))
			{
				$settings['options'] = Lookup::table($source['table'], $source['key'], $source['value']);
			}
			else
			{
				$settings['options'] = Lookup::get($source);
			}
		}

		// get options' values from language file, if available
		if (isset($settings['options']))
		{
			foreach ($settings['options'] as $key => $val) {
				$settings['options'][$key] = Util::lang($val) ?: $val;
			}
		}

		// set default for attributes
		isset($settings['editable']) or $settings['editable'] = true;
		isset($settings['visible'])  or $settings['visible']  = true;
		isset($settings['sortable']) or $settings['sortable'] = false;
		isset($settings['align'])    or $settings['align']    = 'left';
		isset($settings['title-align']) or $settings['title-align'] = false;
		isset($settings['process'])  or $settings['process']  = null;
		isset($settings['format'])   or $settings['format']   = null;
		isset($settings['default'])  or $settings['default']  = null;
	}

	public function add_action($field)
	{
		$this->buttons[] = $field;
	}

	public function sequence(array $build_sequence)
	{
		if ( ! empty($build_sequence))
		{
			$this->sequence = $build_sequence;
		}
	}

	public function build(&$data = array())
	{
		$form_open  = \Form::open(\URL::current(), 'POST', $this->attributes);
		$form_close = \Form::close();

		// TODO: remove this $errors and let each form function detect it
		// from Session instead
		// $errors  = \Session::has('errors') ? \Session::get('errors') : null;
		$errors  = \Session::has('errors') ? \Session::get('errors') : new \Laravel\Messages;

		is_null($this->sequence) and $this->sequence = array_keys($this->fields);

		$fields = '';

		foreach ($this->sequence as $name) {
			// html tag, just output and skip
			if ($name[0] == '<')
			{
				$fields .= $name;
				continue;
			}

			$settings = $this->fields[$name]['settings'];

			// if the field is marked as not visible, just skip it
			if (isset($settings['visible']) and ! $settings['visible'])
			{
				continue;
			}

			$default = $settings['default'] ?: '';
			$value   = \Input::old($name, !empty($data) ? $data->$name : $default);
			$label   = $settings['label'];
			$type    = isset($settings['type']) ? $settings['type'] : 'input';
			$options = isset($settings['options']) ? $settings['options'] : array();
			$attr    = isset($settings['attr']) ? $settings['attr'] : array();

			if (isset($settings['align']))
			{
				$attr = array_merge($attr, array('class' => 'align-'.$settings['align']));
			}

			if (!empty($data) and !$settings['editable'])
			{
				$attr['readonly'] = 'readonly';
			}

			switch($type)
			{
				case 'hidden':
					$fields .= \Form::hidden($name, $value);
					break;
				case 'textarea':
					$fields .= static::textarea($name, $label, $value, $attr, $errors);
					break;
				case 'password':
					$fields .= static::password($name, $label, $value, $attr, $errors);
					break;
				case 'radio':
					$fields .= static::radio_group($name, $label, $options, $value, false, $attr, $errors);
					break;
				case 'radio-inline':
					$fields .= static::radio_group($name, $label, $options, $value, true, $attr, $errors);
					break;
				case 'checkbox':
					$fields .= static::checkbox_group($name, $label, $options, $value, false, $attr, $errors);
					break;
				case 'checkbox-inline':
					$fields .= static::checkbox_group($name, $label, $options, $value, true, $attr, $errors);
					break;
				case 'select':
					$fields .= static::select($name, $label, $value, $options, $attr, $errors);
					break;
				default:
					$fields .= static::text($name, $label, $value, $attr, $errors);
			}
			$fields .= PHP_EOL;
		}

		$fields .= \Form::token();

		$form_actions = static::render_buttons($this->buttons);

		return static::template('form',
			array('{open}', '{fields}', '{form_buttons}', '{close}'),
			array($form_open, $fields, $form_actions, $form_close));
	}

	public static function template($template_name, $keys, $values)
	{
		return str_replace($keys, $values, \Config::get(static::$template.$template_name));
	}

	public static function render_field($fields, $name, $label = '', $errors = null)
	{
		!is_array($fields) and $fields = array($fields);

		$out = '';

		foreach ($fields as $f)
		{
			$out .= $f.PHP_EOL;
		}

		if (is_null($errors))
		{
			$errors = \Session::has('errors') ? \Session::get('errors') : new \Laravel\Messages;
		}

		// $err_msg = ($errors && $errors->has($name)) ? $errors->first($name) : '';
		$err_msg = $errors->has($name) ? $errors->first($name) : '';

		$error_class = empty($err_msg) ? '' : ' '.\Config::get('petro::petro.form.error_class');
		$form_label = static::label($label, array('for' => $name), $err_msg);
		$inline_error = str_replace('{inline_text}', $err_msg, \Config::get('petro::petro.form.inline_error'));

		return static::template('field',
			array('{error_class}', '{label}', '{field}', '{inline_error}'),
			array($error_class, $form_label, $out, $inline_error));
	}

	public static function render_buttons($buttons)
	{
		if (empty($buttons)) return '';

		$out = '';
		foreach ($buttons as $b)
		{
			$out .= $b.PHP_EOL;
		}

		return static::template('form_buttons', '{buttons}', $out);;
	}

	public static function label($text, $attr = array(), $error = '')
	{
		$error_icon = empty($error) ? \Config::get('petro::petro.form.error_icon') : '';

		return static::template('label',
			array('{label_attr}', '{label}', '{error_icon}'),
			array(\HTML::attributes($attr), $text, $error_icon));
	}

	// public static function _input($type, $name, $value = null, $attr = array(), $label = '', $errors = null)
	public static function _input($type, $name, $label = '', $value = null, $attr = array(), $errors = null)
	{
		isset($attr['id']) or $attr['id'] = $name;
		$out = \Form::input($type, $name, $value, $attr);
		return static::render_field($out, $name, $label, $errors);
	}

	// public static function text($name, $value = null, $attr = array(), $label = '', $errors = null)
	public static function text($name, $label = '', $value = null, $attr = array(), $errors = null)
	{
		// return static::_input('text', $name, $value, $attr, $label, $errors);
		return static::_input('text', $name, $label, $value, $attr, $errors);
	}

	// public static function textarea($name, $value = null, $attr = array(), $label = '', $errors = null)
	public static function textarea($name, $label = '', $value = null, $attr = array(), $errors = null)
	{
		// return static::_input('textarea', $name, $value, $attr, $label, $errors);
		return static::_input('textarea', $name, $label, $value, $attr, $errors);
	}

	// public static function password($name, $value = null, $attr = array(), $label = '', $errors = null)
	public static function password($name, $label = '', $value = null, $attr = array(), $errors = null)
	{
		// return static::_input('password', $name, $value, $attr, $label, $errors);
		return static::_input('password', $name, $label, $value, $attr, $errors);
	}

	// public static function select($name, $values = null, $options = array(), $attr = array(), $label = '', $errors = null)
	public static function select($name, $label = '', $values = null, $options = array(), $attr = array(), $errors = null)
	{
		isset($attr['id']) or $attr['id'] = $name;
		$out = \Form::select($name, $options, $values, $attr);

		return static::render_field($out, $name, $label, $errors);
	}

	// public static function radio_group($name, $options = array(), $value = null, $is_inline = false, $attr = array(), $label = '', $errors = null)
	public static function radio_group($name, $label = '', $options = array(), $value = null, $is_inline = false, $attr = array(), $errors = null)
	{
		$is_inline = $is_inline ? 'inline' : '';

		isset($attr['id']) or $attr['id'] = $name;

		$out = '';
		foreach ($options as $key => $val)
		{
			$is_checked = ($key == $value) ? array('checked' => 'checked') : array();
			$f = \Form::radio($name, $key, $is_checked, $attr);
			$out .= static::template('radio_item', array('{is_inline}', '{field}', '{label}'), array($is_inline, $f, $val));
		}

		return static::render_field($out, $name, $label, $errors);
	}

	// public static function checkbox_group($name, $options = array(), $checked = null, $is_inline = false, $attr = array(), $label = '', $errors = null)
	public static function checkbox_group($name, $label = '', $options = array(), $checked = null, $is_inline = false, $attr = array(), $errors = null)
	{
		$is_inline = $is_inline ? 'inline' : '';

		isset($attr['id']) or $attr['id'] = $name;

		$out = '';
		foreach ($options as $key => $val)
		{
			$is_checked = ($key == $checked) ? array('checked' => 'checked') : array();
			$f = \Form::checkbox($name.'_'.$key, $key, $is_checked, $attr);
			$out .= static::template('checkbox_item', array('{is_inline}', '{field}', '{label}'), array($is_inline, $f, $val));
		}

		return static::render_field($out, $name, $label, $errors);
	}

}