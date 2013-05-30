<?php

namespace Petro;

class Breadcrumbs
{

	public static function render($replacement = array())
	{
		$home = \Config::get('petro::petro.breadcrumb.base', 'Home');
		$sep  = \Config::get('petro::petro.breadcrumb.separator', '/');

		$uri = \URI::$segments;
		$last = count($uri)-1;

		if (count($uri) > 1 and $uri[$last] != 'create')
		{
			$last--;
		}

		$link = \URL::base().'/';

		$out = '<span class="breadcrumb">';
		$out .= '<a href="'.$link.'">'.$home.'</a>';

		$i = 0;
		for ($i=0; $i <= $last; $i++)
		{
			$link .= $uri[$i].'/';
			$out .= '<span class="breadcrumb_sep">'.$sep.'</span>';

			$text = $uri[$i];
			$text = isset($replacement[$text]) ? $replacement[$text] : $text;
			$text = strpos($text, '.') ? __($text) : $text;

			if ($i == $last)
			{
				$out .= $text;
			}
			else
			{
				$out .= '<a href="'.$link.'">'.$text.'</a>';
			}
		}

		$out .= '</span>';

		return $out;
	}
}