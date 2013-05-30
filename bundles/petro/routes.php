<?php

Route::controller('petro::auth');
Route::get('auth', 'petro::auth@login');
Route::post('auth', 'petro::auth@login');
Route::get('auth/signup', 'petro::auth@register');
Route::post('auth/signup', 'petro::auth@register');
Route::controller('petro::group');
Route::controller('petro::menu');
Route::controller('petro::permission');

/*
|--------------------------------------------------------------------------
| View Composers
|--------------------------------------------------------------------------
*/

View::composer('petro::navbar', function($view) {
	// site name
	$site_name = Config::get('petro::petro.site_name', 'My Site!!');
	$view->with('site_name', $site_name);

	// menu
	$menus = Petro\Menu::load_from_table();
	$view->with('menus', Petro\Menu::render($menus));

});

