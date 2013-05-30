<?php
namespace Petro;

class Menu
{
	protected static $table = 'menu';

	public function __construct() {
		static::$table = \Config::get('petro::petro.menu.table', 'menu');
	}

	public static function render($menus, $selected = null, $template = null)
	{
		if (count($menus) <= 0) return '';

		if (is_null($template)) $template = \Config::get('petro::petro.template.menu');

		$out = $template['wrapper_start'];
		$out .= static::render_each($menus, $selected, $template);
		$out .= $template['wrapper_end'];

		return $out;
	}

	protected static function render_each($menus, $selected, $template)
	{
		is_null($selected) and $selected = \URI::segment(1);

		$out = '';

		foreach ($menus as $key => $menu)
		{
			if (is_int($key)) $key = $menu;

			if (substr_compare($key, 'divider', 0, 7) == 0)
			{
				$out .= $template['menu_divider'];
				continue;
			}

			isset($menu['label']) or $menu['label'] = Lang::get($key);
			isset($menu['link'])  or $menu['link']  = '#';
			isset($menu['level']) or $menu['level'] = 0;

			// $user = \Session::get('user_info');
			// if ($user['level'] < $menu['level']) continue;

			if (isset($menu['submenu']) and count($menu['submenu']) > 0)
			{
				$active = array_key_exists($selected, $menu['submenu']) ? ' active' : '';
				$out .= str_replace(array('{item_id}', '{active}', '{label}', '{submenu}'),
					array($key, $active, $menu['label'], static::render_each($menu['submenu'], $selected, $template)),
					$template['menu_dropdown']);
			}
			else
			{
				$active = ($selected == $key) ? ' class="active"' : '';
				$out .= str_replace(array('{item_id}', '{active}', '{link}', '{label}'),
					array($key, $active, \URL::base().'/'.$menu['link'], $menu['label']),
					$template['menu_item']);
			}
		}

		return $out;
	}

	public static function item($item_id, $label, $link = '#', $submenu = null)
	{
		return array('item_id' => $item_id, 'label' => $label, 'link' => $link, 'submenu' => $submenu);
	}

	// find the given name in all menu level
	// returns the menu of false if not found
	public static function find($name, $menus)
	{
		$out = false;

		foreach ($menus as $key => $menu) {
			if ($key === $name)
			{
				$out = $menu;
				break;
			}
			elseif (isset($menu['submenu']))
			{
				if ($out = static::find($name, $menu['submenu'])) break;
			}
		}

		return $out;
	}

	protected static function get($group, $parent = null)
	{
		$menu_item = function($data) {
			return array(
				'name'    => $data->name,
				'item_id' => $data->seq,
				'label'   => $data->title,
				'link'    => $data->link,
				'level'   => $data->level,
				'parent'  => $data->parent,
			);
		};

		$db = \DB::table(static::$table)
			->where_active(true)
			->where('group', '=', $group);

		is_null($parent) ? $db->where_null('parent') : $db->where('parent', '=', $parent);

		$result = $db->order_by('seq', 'asc')->get();

		$arr = array();
		foreach ($result as $menu) {
			$arr[$menu->name] = $menu_item($menu);
			$submenu = false;
			if ($menu->has_sub) $submenu = static::get($group, $menu->name);
			if (!empty($submenu)) $arr[$menu->name]['submenu'] = $submenu;
		}

		return $arr;
	}

	public static function load_from_table()
	{
		$menu = static::get('main');
		return $menu;
	}
}