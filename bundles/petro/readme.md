
Model to be used with Petro
---------------------------

The following class properties must be defined explicitly.
- public static $properties

The following class properties are not required, but will
implicitly define.
- public static $table (from Eloquent)
- public static $key (from Eloquent)
- public static $rules
- public static $messages
- public static $uneditables

The following field attributes can be defined and used by Petro.

	public static $properties = array(
		<field_name> => array(
			'label'    => <string>,
			'type'     => <string type_spec>,
			'options'  => <string options_spec>,
			'lookup'   => <string lookup_spec>,
			'sortable' => true|*false,
			'visible'  => *true|false,
			'editable' => *true|false,
			'align'    => *left|center|right,
			'process'  => <string function_name>,
			'format'   => <string format_spec>
		),
	);

Breadcrumbs replacement
-----------------------

	public function before()
	{
		parent::before();	// don't forget this!

		// replace 'client' text with a lang key
		$this->breadcrumbs_replace['client'] = 'client.client';

		// replace 'client' text with a text
		$this->breadcrumbs_replace['client'] = 'customers';
	}

Creating New Module
-------------------
- Define table structure
- Use `php artisan generate:migration <table-name>` to generate the migration
- Modify the generated migration file according to the defined table structure
- Run `php artisan migrate` to execute the migration and create the table
- Use `php artisan db:model <table-name>` to generate the model class from the
  table definition
- Create the Controller class by extending from `Petro_App_Controller`
- Route the controller by creating a route in the routes.php file
	`Route::controller('<controller>');`
- Create a menu item in `menu` table
- Create a permission entry in `permissions` table

