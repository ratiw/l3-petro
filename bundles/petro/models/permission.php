<?php

class Permission extends Eloquent
{
	public static $table = 'permissions';

	public static $timestamps = false;

	public static $properties = array(
		'group' => array(
			'type' => 'select',
			'lookup' => array('table' => 'groups', 'key' => 'id', 'value' => 'name')
		),
		'app',
		'index' => array(
			'type' => 'radio-inline',
			'options' => array('1' => 'Yes', '0' => 'No'),
		),
		'create' => array(
			'type' => 'radio-inline',
			'options' => array('1' => 'Yes', '0' => 'No'),
		),
		'read' => array(
			'type' => 'radio-inline',
			'options' => array('1' => 'Yes', '0' => 'No'),
		),
		'update' => array(
			'type' => 'radio-inline',
			'options' => array('1' => 'Yes', '0' => 'No'),
		),
		'delete' => array(
			'type' => 'radio-inline',
			'options' => array('1' => 'Yes', '0' => 'No'),
		),
		'print' => array(
			'type' => 'radio-inline',
			'options' => array('1' => 'Yes', '0' => 'No'),
		),
	);

}
