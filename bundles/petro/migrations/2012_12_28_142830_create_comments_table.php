<?php

class Petro_Create_Comments_Table {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('comments', function($table) {
			$table->increments('id');
			$table->string('app', 20);
			$table->integer('ref_type');
			$table->integer('ref_id');
			$table->integer('user_id');
			$table->integer('type');
			$table->string('text');
			$table->float('number');
			$table->boolean('deleted')->default(false);
			$table->timestamps();
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
		Schema::drop('comments');
	}

}