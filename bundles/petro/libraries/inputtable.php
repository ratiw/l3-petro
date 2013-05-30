<?php

namespace Petro;

class TableColumn
{
	public $name;
	protected $properties = array();
	protected $rules = array();

	public function __construct($name, $properties = array())
	{
		$this->name = $name;
		$prop = array(
			'col'     => $name,
			'label'   => \Str::title($name),
			'visible' => true,
			'align'   => 'left',
			'type'    => 'text',
			'summary' => null,
			'format'  => null,
			'process' => null,
		);

		$this->set_properties(array_merge($prop, $properties));
	}

	public function set_properties($properties)
	{
		foreach ($properties as $name => $value)
		{
			$this->properties[$name] = $value;
		}
	}

	public function __get($name)
	{
		return $this->properties[$name];
	}

	public function __set($name, $value)
	{
		$this->properties[$name] = $value;
	}

	public function __toString()
	{
		$out = "col=\"{$this->name}\"";
		$out .= $this->properties['visible'] ? '' : ' visible="false"';
		$out .= $this->properties['align'] == 'left' ? '' : " align=\"{$this->properties['align']}\"";
		$out .= $this->properties['type'] == 'text' ? '' : " type=\"{$this->properties['type']}\"";
		$out .= is_null($this->properties['summary']) ? '' : " summary=\"{$this->properties['summary']}\"";
		$out .= is_null($this->properties['format']) ? '' : " format=\"{$this->properties['format']}\"";
		if ( ! empty($this->rules))
		{
			$out .= implode('|', $this->rules);
		}
		return $out;
	}
}

class InputTable
{
	protected $show_row_number = true;
	protected $show_summary_row = true;
	protected $min_rows = 5;
	protected $columns = array();
	protected $summary = null;
	protected $data = null;
	protected $modal = null;

	public static $t = array(
		'table_start'   			=> '<table class="index_table table-striped table-bordered petro-table" align="center" style="width:98%;"{attr}>',
		'table_end'     			=> '</table>',
		'table_header_start'		=> '<thead>',
		'table_header_end'			=> '</thead>',
		'table_header_row_start'    => '<tr>',
		'table_header_row_end'      => '</tr>',
		'table_header_col'          => '<th{attr}>{value}</th>',
		'table_body_start'			=> '<tbody data-count="{items_count}">',
		'table_body_end'			=> '</tbody>',
		'table_body_row_start'      => '<tr>',
		'table_body_row_end'        => '</tr>',
		'table_summary_start'		=> '<tfoot>',
		'table_summary_end'			=> '</tfoot>',
		'table_summary_row_start'   => '<tr>',
		'table_summary_row_end'     => '</tr>',
		'table_col'                 => '<td{attr}>{value}</td>',
		'table_header_row_num_col'  => '<th col="_seq" style="width:5px">&nbsp;</th>',
		'table_header_action_col'   => '<th col="_actions" style="width:80px"></th>',
		'table_empty_col'           => '<td>&nbsp;</td>',
		'table_action_edit'         => '<a href="#{row_id}" class="edit_item">{label}</a>',
		'table_action_delete'       => '<a href="#{row_id}" class="delete_item">{label}</a>',
		'table_action_separator'    => '&nbsp;|&nbsp;',
	);

	public function __construct($col_def, $data = array(), $options = array())
	{
		foreach ($col_def as $col => $prop)
		{
			$this->set_column($col, $prop);
		}
		$this->data = $data;
		$this->set_options($options);
	}

	public function set_options($options)
	{
		foreach ($options as $name => $val)
		{
			$this->$name = $val;
		}
	}

	protected function set_column($name, $prop)
	{
		if (is_int($name))
		{
			$name = $prop;
			$this->columns[$name] = new TableColumn($name);
		}
		else
		{
			if (is_string($prop))
			{
				$this->columns[$name] = new TableColumn($name, array('label' => $prop));
			}
			else
			{
				$this->columns[$name] = new TableColumn($name, $prop);
			}
		}
	}

	public function render()
	{
		$out  = $this->render_header();
		$out .= $this->render_body();
		if ($this->show_summary_row)
		{
			$out .= $this->render_summary();
		}

		$table_attr = str_replace(
			"{attr}",
			is_null($this->modal) ? '' : (' modal="'.$this->modal.'"'),
			static::$t['table_start']
		);

		return $table_attr.$out.static::$t['table_end'];
	}

	private function template($name, $attr, $value)
	{
		return str_replace(
			array('{attr}', '{value}'),
			array(empty($attr) ? '' : ' '.$attr, $value),
			static::$t[$name]
		);
	}

	public function render_header()
	{
		$out = '';

		$out .= static::$t['table_header_start'].static::$t['table_header_row_start'];
		$out .= $this->show_row_number ? static::$t['table_header_row_num_col'] : '';
		foreach ($this->columns as $th => $prop)
		{
			is_int($th) and $th = $prop->name;

			// if ($prop->visible == false) continue;

			// $out .= $this->template('table_header_col', 'col="'.$th.'"', $prop->label);
			$out .= $this->template('table_header_col', $prop, $prop->label);
		}
		$out .= static::$t['table_header_action_col'];
		$out .= static::$t['table_header_row_end'].static::$t['table_header_end'];

		return $out;
	}

	public function render_body()
	{
		// additional columns?
		$extra = $this->show_row_number ? 2 : 1;

		$out = str_replace('{items_count}', count($this->data), static::$t['table_body_start']);

		$cur_row = 0;

		foreach ($this->data as $key => $row)
		{
			$out .= static::$t['table_body_row_start'];
			$out .= $this->show_row_number
				? $this->template('table_col', '', $cur_row + 1)
				: '';
			foreach ($this->columns as $td => $prop)
			{
				is_int($td) and $td = $prop->name;

				// if ($prop->visible == false) continue;

				if (isset($this->summary) and array_key_exists($td, $this->summary))
				{
					$func = 'op_'.$this->summary[$td]['function'];
					// $this->$func($td, $row[$td]);
					$this->$func($td, $row->$td);
				}
				if ( ! is_null($prop->process) and $prop->process instanceof \Closure)
				{
					$cb = $prop->process;
					$value = $cb($row);
				}
				else
				{
	                // $value = $row[$td];
	                $value = $row->$td;
				}
				$out .= $this->template('table_col', '', $value);
			}
			$edit = str_replace(array('{row_id}', '{label}'), array($cur_row, 'Edit'), static::$t['table_action_edit']);
			$del  = str_replace(array('{row_id}', '{label}'), array($cur_row, 'Delete'), static::$t['table_action_delete']);
			$out .= $this->template('table_col', '', $edit.static::$t['table_action_separator'].$del);
			$out .= static::$t['table_body_row_end'];
			$cur_row++;
		}
		// additonal blank row, if necessary
		if ($cur_row < $this->min_rows)
		{
			while ($cur_row < $this->min_rows)
			{
				$out .= static::$t['table_body_row_start'];
				$out .= str_repeat(static::$t['table_empty_col'], count($this->columns) + $extra);
				$out .= static::$t['table_body_row_end'];
				$cur_row++;
			}
		}

		$out .= static::$t['table_body_end'];

		return $out;
	}

	public function render_summary()
	{
		$out  = static::$t['table_summary_start'];
		$out .= static::$t['table_summary_row_start'];
		$out .= $this->show_row_number ? static::$t['table_empty_col'] : '';

		foreach ($this->columns as $td)
		{
			$name  = $td->name;
			$value = '&nbsp;';
			if (isset($this->summary[$name]))
			{
				$div = (strtolower($this->summary[$name]['function']) == 'avg') ? $this->summary[$name]['count'] : 1;
				$value = $this->summary[$name]['value'] / $div;
			}
			$out .= $this->template('table_col', '', $value);
		}

		$out .= $this->template('table_col', '', '');	// actions col
		$out .= static::$t['table_summary_row_end'];
		$out .= static::$t['table_summary_end'];

		return $out;
	}

	public function add_summary($name, $function = 'sum', $attr = array())
	{
		$this->summary[$name] = array('function' => $function, 'value' => 0, 'count' => 0, 'attr' => $attr);
	}

    public function get_summary($name)
    {
        $tmp = $this->summary[$name];
        return $tmp['function'] == 'avg'
            ? ($tmp['value'] / $tmp['count'])
            : $tmp['value'];
    }

	public function op_sum($item, $val)
	{
		isset($this->summary[$item]['value']) or $this->summary[$item]['value'] = 0;

		$this->summary[$item]['value'] += $val;
	}

	public function op_count($item, $val)
	{
		isset($this->summary[$item]['value']) or $this->summary[$item]['value'] = 0;

		$this->summary[$item]['value'] += 1;
	}

	public function op_avg($item, $val)
	{
		isset($this->summary[$item]['value']) or $this->summary[$item]['value'] = 0;
		isset($this->summary[$item]['count']) or $this->summary[$item]['count'] = 0;

		$this->summary[$item]['value'] += $val;
		$this->summary[$item]['count'] += 1;
	}

	public function op_min($item, $val)
	{
		isset($this->summary[$item]['value']) or $this->summary[$item]['value'] = 0;

		if ($this->summary[$item]['value'] > $val)
		{
			$this->summary[$item]['value'] = $val;
		}
	}

	public function op_max($item, $val)
	{
		isset($this->summary[$item]['value']) or $this->summary[$item]['value'] = 0;

		if ($this->summary[$item]['value'] < $val)
		{
			$this->summary[$item]['value'] = $val;
		}
	}
}