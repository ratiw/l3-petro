
	<div class="panel span8 well">
		<div class="panel-content">
			<div class="row-fluid">
				{{ Form::open(URL::current(), 'POST', array('class' => 'form-horizontal')) }}
					<div class="control-group">
						<label class="control-label" for="firstname">First Name</label>
						<div class="controls">
							{{ Form::text('firstname', Input::get('firstname', isset($user) ? $user->metadata['first_name'] : '')) }}
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="lastname">Last Name</label>
						<div class="controls">
							{{ Form::text('lastname', Input::get('lastname', isset($user) ? $user->metadata['last_name'] : '')) }}
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="email">Email</label>
						<div class="controls">
							{{ Form::text('email', Input::get('email', isset($user) ? $user->email : '')) }}
						</div>
					</div>
					<hr>
					<div class="control-group">
						<label class="control-label" for="username">Username</label>
						<div class="controls">
							{{ Form::text('username', Input::get('username', isset($user) ? $user->username : ''), array("data-provide" => "typeahead")) }}
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="password">Password</label>
						<div class="controls">
							{{ Form::password('password') }}
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="password2">Confirm Password</label>
						<div class="controls">
							{{ Form::password('password2') }}
						</div>
					</div>
					<div class="form-actions">
						<button type="submit" class="btn btn-primary">Submit</button>
						{{ HTML::link(Str::lower(URI::segment(1)), 'Cancel', array('class' => 'btn')) }}
					</div>
				{{ Form::close() }}
			</div>
		</div>
	</div>
