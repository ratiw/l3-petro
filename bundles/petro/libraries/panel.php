<?php
namespace Petro;

class Panel
{
	public static function render($title = '&nbsp;', $content, $options = null)
	{
		$class = 'panel';

		if (isset($options) and array_key_exists('class', $options))
		{
			$class .= ' '.$options['class'];
			unset($options['class']);
		}

		return str_replace(array('{class}', '{title}', '{content}'),
			array($class, $title, $content), \Config::get('petro::petro.template.panel'));
	}
}