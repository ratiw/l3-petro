<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>Test Site</title>
    <meta name="viewport" content="width=device-width">
    {{ HTML::style('css/bootstrap.css') }}
    {{ HTML::style('css/bootstrap-responsive.css') }}
    {{ HTML::style('css/petro.css') }}
    {{ HTML::style('css/base.css') }}

    {{ HTML::script('js/jquery-1.8.2.min.js') }}
    {{ HTML::script('js/bootstrap.js') }}
    {{ HTML::script('js/jquery.validate.js') }}
    {{ HTML::script('js/jquery.validator.plugin.js') }}
    {{-- HTML::script('js/jquery.form.js') --}}
    <!-- TODO: find the way to yield jstable.js out only when necessary -->
    {{ HTML::script('js/accounting.min.js') }}
    {{ HTML::script('js/jquery.json-2.3.js') }}
    {{ HTML::script('js/jstable.js') }}

@yield('extra-script')

@yield('extra-css')

</head>
<body>
{{-- TODO: Don't forget to remove me when done. Anbu is causing problem with jquery.validator.plugin.js --}}
{{-- Anbu::render() --}}
    @include('petro::navbar')

    <div class="header">
        @yield('header')
    </div>

    <div class="content">
        @yield('content')
    </div>

    <div class="footer">
        @yield('footer')
    </div>

</body>
</html>