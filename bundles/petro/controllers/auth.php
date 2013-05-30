<?php

class Petro_Auth_Controller extends \Base_Controller
{
	public $restful = true;

	public $layout = 'petro::page';

	public function __construct()
	{
		parent::__construct();

		$this->filter('before', 'auth')->except(array('login', 'logout', 'register', 'not_allow'));
	}

	public function before()
	{
		parent::before();
	}

	public function get_login()
	{
		return \View::make('petro::user.login');
	}

	public function post_login()
	{
		$rules = array(
			'username' => 'required',
			'password' => 'required'
		);

		$input = \Input::get();
		$validation = \Validator::make($input, $rules);

		if ($validation->fails())
		{
			return \Redirect::to(\URI::current())->with_input()->with_errors($validation);
		}

		// try {
		// 	$valid_login = Sentry::login(Input::get('username'), Input::get('password'), Input::get('remember'));

		// 	if ($valid_login)
		// 	{
		// 		return Redirect::to('user');
		// 	}
		// 	else
		// 	{
		// 		$data['errors'] = "Invalid login!";
		// 	}
		// } catch (Sentry\SentryException $e) {
		// 	$data['errors'] = $e->getMessage();
		// }

		$credentials = array(
			'username' => \Input::get('username'),
			'password' => \Input::get('password')
		);

		// dd('POST login: before attempt');
		if (\Auth::attempt($credentials))
		{
			// dd('Login OK');
			$url = \Session::get('url', 'user');
			\Session::forget('url');
			return Redirect::to($url);
		}
		else
		{
			// dd('Login failed');
			return \Redirect::to(\URI::current())->with_input()->with('notify', array('error', 'Invalid login!'));
		}

	}

	public function get_logout()
	{
		// Sentry::logout();
		\Auth::logout();
		// return \Redirect::to('user');
		return "You've been logged out.";
	}

	// public function get_reset()
	// {
	// 	return View::make('petro::auth.reset');
	// }

	// public function post_reset()
	// {
	// 	$data = array();

	// 	$rules = array(
	// 		'username' => 'required',
	// 		'password' => 'required|confirmed',
	// 		'password_confirmation' => 'required'
	// 	);

	// 	$input = Input::get();
	// 	$validation = Validator::make($input, $rules);

	// 	if ($validation->fails()) {
	// 		return Redirect::to('auth/reset')->with_input()->with_errors($validation);
	// 	}

	// 	try {
	// 		$reset = Sentry::reset_password(Input::get('username'), Input::get('password'));

	// 		if (!$reset)
	// 		{
	// 			$data['errors'] = 'There was an issue when reset the password';
	// 		}
	// 	} catch (Sentry\SentryException $e) {
	// 		$data['errors'] = $e->getMessage();
	// 	}

	// 	if (array_key_exists('errors', $data))
	// 	{
	// 		return Redirect::to('user/reset')->with_input()->with('errors', $data['errors']);
	// 	}
	// 	else
	// 	{
	// 		return Redirect::to('auth/login')->with('hash_link', URL::base().'/user/confirmation/'.$reset['link']);
	// 	}
	// }

	public function get_register()
	{
		// return View::make('user.signup');

		$this->layout->page_title = 'New Member';
		$this->layout->nest('content', 'petro::user.signup'); //->with('page_title', 'New User');
	}

	public function post_register()
	{
		// data pass to the view
		$data = array();

		// do validation
		$rules = array(
			'email' => 'required',
			'password' => 'required|confirmed',
			'password_confirmation' => 'required'
		);

		$input = Input::get();
		$validation = Validator::make($input, $rules);

		if ($validation->fails()) {
			return Redirect::to('user/register')->with_input()->with_errors($validation);
		}

		// add user
		// try {
		// 	$user = Sentry::user()->register(array(
		// 		'email' => Input::get('email'),
		// 		'password' => Input::get('password')
		// 	));

		// 	if (!user)
		// 	{
		// 		$data['errors'] = 'There was an issue when add user to database';
		// 	}
		// } catch (Sentry\SentryException $e) {
		// 	$data['errors'] = $e->getMessage();
		// }

		$user = new User;
		$user->username = \Input::get('username');
		$user->password = Hash::make(\Input::get('password'));

		if ( ! $user->save())
		{
			$data['errors'] = 'Error adding new user.';
		}

		if (array_key_exists('errors', $data)) {
			return Redirect::to('user/register')->with_input()->with('errors', $data['errors']);
		}
		else
		{
			return Redirect::to('user/login')->with('hash_link', URL::base().'/user/activate/'.$user['hash']);
		}
	}

	// public function get_activate($email = null, $hash = null)
	// {
	// 	try {
	// 		$activate_user = Sentry::activate_user($email, $hash);

	// 		if ($activate_user)
	// 		{
	// 			return Redirect::to('user/login');
	// 		}
	// 		else
	// 		{
	// 			echo "The user was not activated";
	// 		}
	// 	} catch (Sentry\SentryException $e) {
	// 		echo $e->getMessage();
	// 	}
	// }

	// public function get_confirmation($email = null, $hash = null)
	// {
	// 	try {
	// 		$confirmation = Sentry::reset_password_confirm($email, $hash);

	// 		if ($confirmation) {
	// 			return Redirect::to('user/login');
	// 		} else {
	// 			echo 'Unable to reset password';
	// 		}
	// 	} catch (Sentry\SentryException $e) {
	// 		echo $e->getMessage();
	// 	}
	// }
}