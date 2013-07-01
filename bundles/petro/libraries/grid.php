<?php
namespace Petro;

class Grid
{

	protected $model = null;

	protected $fields = null;

	protected $summary = array();

	// protected $petro_app = null;

	protected $pagination = null;

	protected $scopes = null;

	// when not empty, must be in the following format
	// array('col' => <sort-column-name>, 'dir' => <sort-direction>)
	protected $order_by = array();

	protected $page = 1;

	protected $per_page = 10;

	protected $options = array();

	protected $filters = null;


	public function __construct($model, $fields = null)
	{
		// if ($model instanceof App_Controller)
		// {
		// 	$this->petro_app = $model;
		// 	$model = $this->petro_app->model;
		// }

		$this->model = $model;
		$this->scopes = new GridScopes($model);
		$this->fields = AutoForm::grab_fields($model);
		$this->fields['_actions_'] = static::default_actions();

		$this->options['show_row_no'] = true;
		$this->options['show_page_info'] = true;

	}

	public function render($fields = null, $page = 1, $order = null, $scope = null, $filters = null)
	{
		$this->set_page($page);
		$this->set_order_by(is_null($order) ? $this->order_by : $order);
		$this->set_scope($scope);
		$this->set_filters($filters);

		$model = $this->model;

		$query = $model::order_by($this->order_by['col'], $this->order_by['dir']);
		$this->setup_query_conditions($query);

		$pagination = $query->paginate($this->per_page);
		$pagination->appends(array(
			'order' => implode('_', $this->order_by),
			'scope' => $this->scopes->selected())
		);

		$data['scopes'] = $this->scopes->render($this);
		$data['grid']   = $this->make_grid($fields);
		$data['page_info']  = $this->make_page_info($pagination);
		$data['pagination'] = $pagination->links();

		return \View::make('petro::templates.grid', $data);
	}

	public function set_page($page)
	{
		$this->page = (is_null($page) or $page < 1) ? 1 : $page;
	}

	public function set_order_by($order = null)
	{
		if (empty($order) || is_null($order))
		{
			if ($order = $this->find_first_visible_field())
			{
				$order = array('col' => $order, 'dir' => 'asc');
			}

		}

		if (is_array($order))
		{
			$this->order_by = array(
				'col' => \Str::lower($order['col']),
				'dir' => in_array(\Str::lower($order['dir']), array('asc','desc')) ? $order['dir'] : 'asc',
			);
		}
		else
		{
			$order = \Str::lower($order);
			$col = Util::split_last('_', $order);

			$this->order_by = array(
				'col' => $col[0],
				'dir' => $col[1]
			);
		}
	}

	public function set_scope($scope)
	{
		$this->scopes->select($scope);
	}

	/**
	 * $filters can be a query string
	 *		aa_eq=123&bb_lt=456&cc_gt=789)
	 *
	 * or an array from Input::get('q'),
	 *		Array(
	 *			'aa_contains' => 123,
	 *			'bb_eq' => 456
	 *		)
	 */
	public function set_filters($filters)
	{
		$this->filters = is_null($filters) ? null : Util::q2a($filters);
	}

	public function add_scope($name, $label, $criteria = null, $link = '#')
	{
		$this->scopes->add($name, $label, $criteria, $link);
	}

	public function add_summary($name, $func = 'sum')
	{
		$this->summary[$name] = array(
			'function' => $func,
			'value'    => null,
		);
	}

	public function make_page_info(&$pagination)
	{
		if ( ! $this->options['show_page_info']) return '&nbsp;';

		$page = \Input::query('page', 1);
		$from = $this->per_page * ($page - 1) + 1;
		$to   = ($page == $pagination->last) ? $pagination->total : $from + $this->per_page - 1;
		$total = $pagination->total;

		if ($total == 0)
		{
			$page_info = 'No data to display.';
		}
		else
		{
			$page_info = 'Displaying <b>{from} - {to}</b> of <b>{total}</b> in total';
			$page_info = str_replace(array('{from}', '{to}', '{total}'),
				array($from, $to, $total), $page_info);
		}

		return \View::make('petro::templates.grid_pageinfo')
			->with('page_info', $page_info);
	}

	public function make_link($page = null, $order = null, $scope = null)
	{
		$q = array(
			'page'  => is_null($page) ? \Input::query('page', 1) : $page,
			'order' => is_null($order) ? implode('_', $this->order_by) : $order,
			'scope' => $scope
		);
		$filters = isset($this->filters) ? Util::a2q($this->filters) : '';
		return \URL::base().'/'.\URI::segment(1).'?'.http_build_query($q).'&'.$filters;
	}

	public function find_first_visible_field()
	{
		foreach ($this->fields as $name => $prop)
		{
			if ($prop['settings']['visible']) return $name;
		}

		return false;
	}

	public function make_grid($columns = null)
	{
		if (!isset($columns) || empty($columns))
		{
			$columns = array_keys($this->fields);
		}

		$data['fields']   = $this->fields;
		$data['columns']  = $columns;
		$data['order_by'] = $this->order_by;
		//...
		$data['grid_header'] = $this->make_grid_header($columns);
		$data['grid_footer'] = $this->make_grid_summary($columns);
		$data['grid_body']   = $this->make_grid_body($columns);
		return \View::make('petro::templates.grid_table', $data);
	}

	public function make_grid_header($columns)
	{
		$out = '';

		if ($this->options['show_row_no'])
		{
			$out .= '<th class="_seq_">&nbsp;</th>';
		}

		foreach ($columns as $name)
		{
			$class = ($name == '_actions_') ? $name : '';

			$settings = $this->fields[$name]['settings'];

			if ( ! $settings['visible']) continue;

			$settings['title-align'] and $class .= ' '.$settings['title-align'];

			if ($settings['sortable'])
			{
				$class .= ' sortable';

				if ($name == $this->order_by['col'])
				{
					$class .= ' sorted-'.$this->order_by['dir'];
					$order = $name.'_'.($this->order_by['dir'] == 'asc' ? 'desc' : 'asc');
				}
				else
				{
					$order = $name.'_asc';
				}
				$url = $this->make_link(1, $order, $this->scopes->selected());
				$out .= '<th class="'.trim($class).'">';
				$out .= '<a href="'.$url.'">'.$settings['label'].'</a>';
				$out .= '</th>';
			}
			else
			{
				$out .= '<th'.(empty($class) ? '' : ' class="'.$class.'"').'>'.$settings['label'].'</th>';
			}
		}

		return $out;
	}

	public function make_grid_body($columns)
	{
		$model = $this->model;

		$page = \Input::query('page', 1);
		$row_count = ($page -1) * $this->per_page;

		$query = $model::order_by($this->order_by['col'], $this->order_by['dir']);

		$this->setup_query_conditions($query);

		$data = $query->skip($row_count)->take($this->per_page)->get();

		$out = '';

		foreach ($data as $row)
		{
			$row_count++;
			$alt = ($row_count % 2) == 0 ? 'even' : 'odd';

			$out .= '<tr class="'.$alt.'">';

			if ($this->options['show_row_no'])
			{
				$out .= '<td class="_seq_">'.$row_count.'</th>';
			}

			foreach ($columns as $name)
			{
				$settings = $this->fields[$name]['settings'];

				if ( ! $settings['visible']) continue;

				$value = '';

				if (isset($settings['options']) and !empty($settings['options']))
				{
					$value = isset($settings['options'][$row->$name]) ? $settings['options'][$row->$name] : 'ERROR!!';
				}
				else
				{
					$value = isset($row->$name) ? $row->$name : '';
				}

				if (isset($settings['process']) and !empty($settings['process']))
				{
					if ($settings['process'] instanceof \Closure)
					{
						$value = $settings['process']($row, $value);
					}
					elseif (is_string($settings['process']))
					{
						$value = call_user_func(array($this->model, $settings['process']), $row, $value);
					}
				}

				if (isset($settings['format']))
				{
					$value = Util::format($settings['format'], $value);
				}

				$align = (isset($settings['align']) and $settings['align'] != 'left') ? ' class="align-'.$settings['align'].'"' : '';

				$out .= '<td'.$align.'>'.$value.'</td>';
			}

			$out .= '</tr>';
		}

		return $out;
	}

	public function make_grid_summary($columns)
	{
		if (count($this->summary) < 1) return '';

		isset($columns) or $columns = array_keys($this->fields);

		$foot = '';

		foreach ($columns as $col)
		{
			$prop = $this->fields[$col];
			if (isset($prop['visible']) and $prop['visible'] == false) continue;

			$align = isset($prop['align']) ? ' class="align-'.$prop['align'].'"' : '';

			if (array_key_exists($prop['name'], $this->summary))
			{
				$value = $this->summary[$prop['name']]['value'];
				if (isset($prop['format']))
				{
					$value = Util::format($prop['format'], $value);
				}

				$foot .= '<td'.$align.'>'.$value.'</td>';
			}
			else
			{
				$foot .= '<td></td>';
			}
		}

		return '<tfoot><tr>'.$foot.'</tr></tfoot>';
	}

	public function fetch_summary()
	{
		$model = $this->model;
		// $sum = '';
		$sum = array();

		foreach ($this->summary as $name => $prop)
		{
			// $sum .= empty($sum) ? '' : ', ';
			// $sum .= \Str::upper($prop['function'])."($name) as $name";
			$sum[] = DB::raw(\Str::upper($prop['function'])."($name) as $name");
		}

		$query = $model::select($sum);
		$this->setup_query_conditions($query);

		$data = $query->get();

		foreach ($data as $key => $val)
		{
			$this->summary[$key]['value'] = $val;
		}
	}

	private function setup_query_conditions(&$query)
	{
		// get criteria of the currently selected scope
		$criteria = $this->scopes->get_criteria();

		if ( ! is_null($criteria))
		{
			$query->where($criteria[0], $criteria[1], $criteria[2]);
		}

		if ( ! is_null($this->filters))
		{
			foreach ($this->filters as $filter => $c)
			{
				$query->where($c[0], $c[1], $c[2]);
			}
		}

		return $query;
	}

	public static function default_actions()
	{
		return AutoForm::add_field('_actions_', '', array(
			'label' => '',
			'process' => function($data) {

				$action = function($t, $url, $label) {
					return str_replace(
						array('{url}', '{label}'),
						array($url, $label),
						\Config::get('petro::petro.template.grid.'.$t));
				};

				$base = \URL::current().'/';
				$out  = $action('default_action_view', $base.'view/'.$data->id, 'View');
				$out .= '&nbsp;'.$action('default_action_edit', $base.'edit/'.$data->id, 'Edit');
				$out .= '&nbsp;'.$action('default_action_delete', $base.'destroy/'.$data->id, 'Delete');
				return $out;
			}
		));
	}
}