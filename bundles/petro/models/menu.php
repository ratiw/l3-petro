<?php

class Menu extends Eloquent
{
	public static $table = 'menu';

	public static $properties = array(
		'id',
		'group',
		'name',
		'title',
		'title_en',
		'seq',
		'link',
		'has_sub',
		'parent',
		'level',
		'active',
	);

}
