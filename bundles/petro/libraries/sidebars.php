<?php

namespace Petro;

class Sidebars
{
	public static $template = null;

	protected $sidebars = array();

	public function add($title, $contents)
	{
		$this->sidebars[$title] = $contents;
		return $this;
	}

	public function remove($title)
	{
		if ($this->exists($title)) unset($this->sidebars[$title]);
	}

	public function exists($title)
	{
		return array_key_exists($title, $this->sidebars);
	}

	public function clear()
	{
		unset($this->sidebars);
		$this->sidebars = array();
	}

	public function count()
	{
		return count($this->sidebars);
	}

	public function render($title = null)
	{
		if (count($this->sidebars) < 1) return null;

		if (is_null(static::$template)) static::$template = \Config::get('petro::petro.template.sidebar');

		$out = static::$template['wrapper_begin'];

		foreach ($this->sidebars as $key => $value)
		{
			if (isset($title) and $title != $key) continue;

			$out .= static::$template['section_begin'];
			$out .= static::$template['section_head_begin'];
			$out .= $key;
			$out .= static::$template['section_head_end'];
			$out .= static::$template['contents_begin'];
			$out .= $value;
			$out .= static::$template['contents_end'];
			$out .= static::$template['section_end'];
		}

		$out .= static::$template['wrapper_end'];

		return $out;
	}
}