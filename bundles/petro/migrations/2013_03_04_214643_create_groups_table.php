<?php

class Petro_Create_Groups_Table {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('groups', function($table) {
			$table->increments('id');
			$table->string('name');
			$table->string('description');
		});	
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('groups');
	}

}