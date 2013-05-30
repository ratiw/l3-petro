<?php
namespace Petro;

class GridScopes
{
	protected $model = null;

	public $scopes = array();

	public $selected_scope = null;

	public function __construct($model)
	{
		if ( ! class_exists($model))
		{
			throw new Exception('Class not existed: '.$model);
		}

		$this->model = $model;
	}

	public function add($name, $label, $criteria = null, $link = '#')
	{
		$this->scopes[$name] = array(
			'label'    => $label,
			'criteria' => $criteria,
			'link'     => $link,
			'count'    => 0,
		);
	}

	public function remove($name)
	{
		if (array_key_exists($name, $this->scopes))
		{
			unset($this->scopes[$name]);
		}
	}

	public function clear()
	{
		if ( ! empty($this->scopes))
		{
			$this->scopes = array();
		}
	}

	public function exists($name)
	{
		return array_key_exists($name, $this->scopes);
	}

	public function count()
	{
		return count($this->scopes);
	}

	public function get_label($name)
	{
		return $this->exists($name) ? $this->scopes[$name]['label'] : '';
	}

	public function get_criteria($name = null)
	{
		if (is_null($name) and !is_null($this->selected_scope))
		{
			return $this->scopes[$this->selected_scope]['criteria'];
		}
		else
		{
			return $this->exists($name) ? $this->scopes[$name]['criteria'] : null;
		}
	}

	public function get_count($name = null)
	{
		$model = $this->model;

		if (is_null($name))
		{
			return $model::count();
		}
		elseif ($this->exists($name))
		{
			$c = $this->scopes[$name]['criteria'];
			return $this->scopes[$name]['count'] = is_null($c) ? $model::count() : $model::where($c[0], $c[1], $c[2])->count();
		}
		else
		{
			return false;
		}
	}

	public function select($name)
	{
		if ($this->exists($name))
		{
			$this->selected_scope = $name;
			return true;
		}

		return false;
	}

	public function selected()
	{
		return $this->selected_scope;
	}

	public function render(&$grid)
	{
		if (empty($this->scopes)) return '';

		$selected = $this->selected_scope;

		foreach ($this->scopes as $scope => &$prop)
		{
			// if scope is not selected, select the first one by default
			isset($selected) or $selected = $scope;
			// refresh the count for each scope
			$this->get_count($scope);
			$prop['link'] = $grid->make_link(1, null, $scope);
		}

		return \View::make('petro::templates.grid_scope')
			->with('scopes', $this->scopes)
			->with('selected_scope', $selected);
	}
}