<?php

class Petro_Create_Permissions_Table {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('permissions', function($table) {
			$table->increments('id');
			$table->integer('group');
			$table->string('app', 20);
			$table->boolean('index');
			$table->boolean('create');
			$table->boolean('read');
			$table->boolean('update');
			$table->boolean('delete');
			$table->boolean('print');
			$table->unique(array('group', 'app'));
			$table->engine = 'InnoDB';
		});
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('permissions');
	}

}