<?php

Autoloader::namespaces(array(
  'Petro' => Bundle::path('petro') . 'libraries'
));

Autoloader::directories(array(
  Bundle::path('petro') . 'models',
  // Bundle::path('petro') . 'controllers',
));

Autoloader::map(array(
	'Petro_Auth_Controller' => Bundle::path('petro').'controllers/auth.php',
	'Petro_App_Controller'  => Bundle::path('petro').'controllers/app.php',
));

Petro\Lookup::_init();
