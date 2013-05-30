@layout('petro::layout.common')
@section('extra-css')
    <style type="text/css">
      body {
        padding-bottom: 40px;
        background-color: #f5f5f5;
      }

      .form-signin {
        max-width: 300px;
        padding: 19px 29px 29px;
        margin: 100px auto 20px;
        background-color: #fff;
        border: 1px solid #e5e5e5;
        -webkit-border-radius: 5px;
           -moz-border-radius: 5px;
                border-radius: 5px;
        -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
           -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
                box-shadow: 0 1px 2px rgba(0,0,0,.05);
      }
      .form-signin h2.form-signin-heading {
        padding-bottom: 16px;
      }
      .form-signin .form-signin-heading,
      .form-signin .checkbox {
        margin-bottom: 10px;
      }
      .form-signin input[type="text"],
      .form-signin input[type="password"] {
        font-size: 16px;
        height: auto;
        margin-bottom: 15px;
        padding: 7px 9px;
      }
    </style>
@endsection

@section('content')

<div class="container">
    {{ Auth::check() ? "OK" : "Nope"}}
    {{ '<br/>'}}
    {{ Session::get('url', "Blank") }}
    {{ Form::open('auth/login', 'POST', array('class' => 'form-signin')) }}

        <h2 class="form-signin-heading">Please sign in</h2>

        <!-- username field -->
        {{ Form::text('username', '', array('class' => 'input-block-level','placeholder' => 'Username', 'autofocus' => 'autofocus')) }}

        <!-- password field -->
        {{ Form::password('password', array('class' => 'input-block-level','placeholder' => 'Password')) }}

        <!-- submit button -->
        {{ Form::submit('Sign in', array('class' => 'btn btn-large btn-primary')) }}

  <!-- check for login errors flash var -->
  @if (Session::has('errors'))
      <p>&nbsp;</p><span style="color:red">Both username and password are required.</span>
  @endif

    {{ Form::close() }}
</div>

@endsection

@section('footer')
    <hr>
    Footer here -- copyright &copy;
@endsection
