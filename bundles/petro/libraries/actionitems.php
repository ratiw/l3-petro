<?php

namespace Petro;

class ActionItems
{
	protected $items = array();

	public function add($title, $link, $attr = array(), $visible = true)
	{
		$this->items[] = array(
			'title' => $title,
			'link' => $link,
			'attr' => $attr,
			'visible' => $visible
		);
	}

	public function remove($index)
	{
		if ($index > 0 && $index < count($this->items))
		{
			unset($this->items[$index]);
		}
	}

	public function clear()
	{
		unset($this->items);
		$this->items = array();
	}

	public function count()
	{
		return count($this->items);
	}

	public function render()
	{
		if (count($this->items) <= 0) return '';

		$out = '';

		foreach ($this->items as $act)
		{
			if (isset($act['visible']) and $act['visible'] == false)
			{
				continue;
			}

			$btn  = 'btn pull-right';
			if (empty($act['attr']))
			{
				$attr = array('class' => $btn);
			}
			else
			{
				$attr = $act['attr'];
				if (isset($attr['class']))
				{
					$attr['class'] = $attr['class'] . ' ' . $btn;
				}
				else
				{
					$attr['class'] = $btn;
				}
			}

			$out = '<span class="action_item">'.\HTML::link($act['link'], $act['title'], $attr) . '</span>' . $out;
		}

		return $out;
	}
}