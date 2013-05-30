<?php

class Petro_Create_Lookup_Table {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		// Create lookup table
		Schema::create('lookup', function($table) {
			$table->increments('id');
			$table->string('type', 20);
			$table->string('code', 20);
			$table->string('name');
			$table->integer('seq');
			$table->timestamps();
			$table->engine = 'InnoDB';
		});

		$this->insert_sample_data();
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		// Drop the table
		Schema::drop('lookup');
	}

	public function insert($type, $code, $name, $seq)
	{
		\Db::table('lookup')->insert(array(
			'type' => $type,
			'code' => $code,
			'name' => $name,
			'seq'  => $seq,
		));
	}

	public function insert_sample_data()
	{
		// $this->insert();
	}
}