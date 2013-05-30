<?php

class Petro_Create_Users_Table {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function($table) {
			$table->increments('id');
			$table->string('username', 50);
			$table->string('email', 50);
			$table->string('password');
			$table->string('password_reset_hash')->nullable();
			$table->string('temp_password')->nullable();
			$table->integer('group')->unsigned();
			$table->string('remember_me')->nullable();
			$table->string('activation_hash')->nullable();
			$table->string('status');
			$table->string('activated')->nullable();
			$table->string('ip_address');
			$table->timestamp('last_login');
			$table->timestamps();
		});
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');
	}

}