<?php

class Petro_Menu_Controller extends Petro_App_Controller
{
	protected $model = 'Menu';

	public $page_title = 'Menu';

	// public $form_columns = array();

	public $grid_columns = array('seq', 'group', 'name', 'title', 'title_en', 'link', 'has_sub', 'parent', 'level', 'active', '_actions_');


	protected function setup_index(&$grid)
	{
		$grid->set_order_by('seq_asc');
	}
}