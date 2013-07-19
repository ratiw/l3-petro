<?php

class Petro_App_Controller extends Petro_Auth_Controller
{
	public $restful = true;

	/**
	 * will store url reference of this app, which is the first segment
	 * of the URI.
	 *
	 * @var string
	 */
	protected $app_url = null;

	/**
	 * Store the human readable name of the app.
	 * If unset, will use the $app_url with the first character
	 * capitalized. Will be used when $page_title is not set.
	 *
	 * @var string
	 */
	protected $app_name = null;

	/**
	 * Name of the Eloquent Model to be used.
	 *
	 * @var string
	 */
	protected $model = null;

	/**
	 * Text to be shown as page title.
	 *
	 * @var string
	 */
	protected $page_title = null;

	/**
	 * Store action items to be generated.
	 *
	 * @var Petro\ActionItems;
	 */
	protected $action_items;

	/**
	 * Store sidebars to be generated.
	 *
	 * @var Petro\Sidebars
	 */
	public $sidebars;

	/**
	 * Breadcrumbs replacement array
	 * @var array
	 */
	protected $breadcrumbs_replace = array();

	/**
	 * Define which columns are to be displayed
	 * @var array
	 */
	protected $grid_columns = null;

	protected $view_columns = null;

	protected $form_columns = null;

	/**
	 * Option to stay on new (create) page after save (insert)
	 * @var boolean
	 */
	protected $stay_on_new = false;

	/**
	 * Initialize properties
	 */
	public function __construct()
	{
		parent::__construct();

		$this->filter('before', 'csrf')->on('post');

		// usually plural of the model, in lower-case
		$this->app_url = \URI::segment(1);

		// name of the app, usually first character capitalize
		if (is_null($this->app_name))
		{
			$this->app_name = \Str::title($this->app_url);
		}

		if (is_null($this->model))
		{
			$this->model = \Str::singular($this->app_name);
		}

		// if page_title is not set, guess one from app_name or app_url
		if (is_null($this->page_title))
		{
			$this->page_title = \Str::title( \Str::plural(($this->app_name) ?: $this->app_url) );
		}

		$this->action_items = new Petro\ActionItems;

		$this->sidebars = new Petro\Sidebars;

	}

	public function before()
	{
		parent::before();
	}

	public function after($response)
	{
		$this->layout->page_title = Lang::line($this->model.'.'.$this->page_title)->get(null, $this->page_title);

		// render breadcrumbs
		$this->layout->breadcrumbs = Petro\Breadcrumbs::render($this->breadcrumbs_replace);

		// render action_items, if any
		if ($this->action_items->count() > 0)
		{
			$this->layout->action_items = $this->action_items->render();
		}

		// render the sidebar, if any
		if ($this->sidebars->count() > 0)
		{
			$this->layout->sidebars = $this->sidebars->render();
		}
	}

	public function set_page_title($title)
	{
		$this->page_title = $title;
	}

	public function set_content($content)
	{
		$this->layout->content = $content;
	}

	// GET foo/
	// show index (table grid)
	public function get_index()
	{
		$this->set_content($this->index());
	}

	// handles filters post
	public function post_index()
	{
		// $this->set_content($this->index());
		return var_dump(\Input::all());
	}

	// GET foo/create
	// show create form
	public function get_create()
	{
		$this->set_content($this->create());
	}

	// POST foo/
	// post create form
	public function post_create()
	{
		return $this->store();
	}

	// GET foo/view/{id}
	// show record data
	public function get_view($id)
	{
		$this->set_content($this->show($id));
	}

	// GET foo/edit/{id} *** it is GET foo/{id} in L4
	// show edit form
	public function get_edit($id)
	{
		$this->set_content($this->edit($id));
	}

	// POST foo/edit/{id} *** it is POST foo/{id} in L4
	// post edit form
	public function post_edit($id)
	{
		return $this->update($id);
	}

	// POST foo/destroy/{id} *** it is DELETE foo/{id} in L4
	public function get_destroy($id)
	{
		return $this->destroy($id);
	}

	public function post_comment()
	{
		$text = Input::get('comment_text');

		if ( ! empty($text))
		{
			$comment = array(
				'ref_type' => Input::get('comment_ref_type'),
				'ref_id'   => Input::get('comment_ref_id'),
				'user_id'  => \Auth::user()->id,
				'type'     => Input::get('comment_type'),
				'text'     => $text
			);

			if (Comment::create($comment))
			{
				$notify = array('notice', 'Comment was successfully created.');
			}
			else
			{
				$notify = array('error', 'Could not add new comment.');
			}
		}

		return Redirect::to(Input::get('last_url'))->with('notify', $notify);
	}


	// L4-liked resourceful controller functions
	public function index()
	{
		$grid = new Petro\Grid($this->model);

		$this->setup_index($grid);

		$this->action_items->add(
			Petro\Util::lang('petro.action_item:btn:add_new', 'Add New').\Str::singular($this->page_title), $this->app_url.'/create'
		);

		return $grid->render(
			$this->grid_columns,
			\Input::query('page', 1),
			\Input::query('order'),
			\Input::query('scope'),
			\Input::get('q')
		);
	}

	public function create()
	{
		$this->set_page_title(Petro\Util::lang('petro.page_title:label:new', 'New ').\Str::singular($this->page_title));
		// $form = $this->setup_form();
		// return $form->build();
		return $this->setup_form();
	}

	public function store()
	{
		$validation = $this->setup_validation();

		if ($validation->fails())
		{
			return \Redirect::to(\URL::current())->with_input()->with_errors($validation);
		}
		else
		{
			$data = $this->get_input_data();

			// if the extended class define 'before_insert' method, call it
			// this method must return the updated data
			if ($this->before_insert($data) == false)
			{
				return \Redirect::to($this->app_url)
					->with('notify', array('error', 'Save cancelled by user.'));
			}

			if ($this->save_data($data))
			{
				$this->after_insert();
				return \Redirect::to($this->stay_on_new ? \URI::current() : $this->app_url)
					->with('notify', array('success', "Data has been saved."));
			}
			else
			{
				return \Redirect::to($this->app_url)
					->with('notify', array('error', "Data has not been saved!"));
			}
		}
	}

    public function setup_validation($edit_mode = false)
    {
    	$model = $this->model;

        $input = \Input::get();
        $uneditables = isset($model::$uneditables) ? $model::$uneditables : array();
        $messages    = isset($model::$messages)    ? $model::$messages    : array();
        $rules       = isset($model::$rules)       ? $model::$rules       : array();

        // remove validation rules of the uneditables
        if ($edit_mode)
        {
            $rules = array_diff_key($rules, array_flip($uneditables));
        }

        return \Validator::make($input, $rules, $messages);
    }

    public function get_input_data(&$data = null)
    {
        $model = $this->model;

        if (is_null($data))
        {
        	$data = new $model;
        	$uneditables = array();
        }
        else
        {
	        $uneditables = isset($model::$uneditables) ? $model::$uneditables : array();
        }

        // get all input and remove uneditable keys including the csrf token
        $input = array_diff_key(\Input::all(), array_flip($uneditables), array_flip(array('csrf_token')));
        // allow the user to filter some more, if necessary
        $input = $this->filter_input($input);

        $data->fill($input);

        return $data;
    }

    public function filter_input(&$input)
    {
    	// by default, this method just return the original input
    	return $input;
    }

	public function show($id)
	{
		$model = $this->model;

		$data = $model::find($id);
		if (is_null($data))
		{
			return \Redirect::to(\URL::current())
				->with('notify',
					array('error', \Lang::line('petro.show:not_found:msg', array('id' => $id))->get())
				);
		}
		else
		{
			$name_lc = \Str::lower($this->app_name);

			$this->action_items->add(Petro\Util::lang('petro.action_item:btn:edit', 'Edit').\Str::singular($this->app_name), $name_lc.'/edit/'.$id);
			$this->action_items->add(Petro\Util::lang('petro.action_item:btn:delete', 'Delete').\Str::singular($this->app_name), $name_lc.'/destroy/'.$id,
				array('data-toggle' => 'modal', 'data-target' => '#petro-confirm', 'class' => 'del-item',)
			);
		}

		// $this->set_page_title($data->name);

		$content = Petro\Panel::render(
				$this->app_name.Petro\Util::lang('petro.panel:label:information', ' Information'),
				Petro\AttrTable::render($data, $this->view_columns)
				);

		$content .= $this->setup_view($data);

		$content .= Petro\Comment::render($this->app_url, $id);

		return $content;
	}

	public function edit($id)
	{
		$model = $this->model;

		$this->set_page_title(Petro\Util::lang('petro.page_title:label:edit', 'Edit ').\Str::singular($this->page_title));

		if (is_null($data = $model::find($id)))
		{
			return "Data not found. id#$id";
		}

		return $this->setup_form($data);
	}

	public function update($id)
	{
		$validation = $this->setup_validation(true);

		if ($validation->fails())
		{
			return \Redirect::to(\URL::current())->with_input()->with_errors($validation);
		}
		else
		{
			$model = $this->model;
			$data = $model::find($id);

			if (is_null($data))
			{
				return \Redirect::to($this->app_url)->with('notify', array('error', 'Record no longer exist!'));
			}

			$data = $this->get_input_data($data);

			// if the extended class has defined 'before_update' method, call it.
			// if the method returns true, then updated data, or else cancel the update
			if ( ! $this->before_update($data))
			{
				return \Redirect::to($this->app_url)
					->with('notify', array('error', 'Update cancelled by user.'));
			}

			if ($this->update_data($data))
			{
				$this->after_update();
				return \Redirect::to($this->app_url)
					->with('notify', array('success', "Data has been updated."));
			}
			else
			{
				return \Redirect::to($this->app_url)
					->with('notify', array('error', "Data has not been updated!"));
			}

		}
	}

	public function destroy($id)
	{
		$model = $this->model;
		$data = $model::find($id);

		if ( ! $this->before_delete($id))
		{
			return \Redirect::to($this->app_url)
				->with('notify', array('notice', 'Delete cancelled by user.'));
		}
		else
		{
			if ($this->delete_data($data))
			{
				$this->after_delete();
				return \Redirect::to($this->app_url)
					->with('notify', array('success', 'Record has successfully been deleted.'));
			}
			else
			{
				return \Redirect::to($this->app_url)
					->with('notify', array('error', 'Could not delete data.'));
			}
		}
	}

	/**
	 * CRUD actions
	 *
	 */

	// note the pass-by-reference declaration
	protected function setup_index(&$grid) {}

	protected function setup_view(&$data) {}

	protected function setup_form(&$data = array())
	{
		$form = new Petro\AutoForm(array('class' => 'form-horizontal'));
		$form->add_model($this->model);
		$form->add_action(\Form::submit('Submit', array('class' => 'btn btn-primary')));
		$form->add_action(\HTML::link($this->app_url, 'Cancel', array('class' => 'btn')));

		isset($this->form_columns) and $form->sequence($this->form_columns);

		return $form->build($data);
	}

	public function before_insert(&$data) { return true; }

	public function save_data(&$data)
	{
		return $data->save();
	}

	public function after_insert() {}

	public function before_update(&$data) { return true; }

	public function update_data(&$data)
	{
		return $data->save();
	}

	public function after_update() {}

	public function before_delete($id) { return true; }

	public function delete_data(&$data)
	{
		return $data->delete();
	}

	public function after_delete() {}

}