
    <div class="navbar navbar-inverse navbar-fixed-top" data-dropdown="dropdown">
        <div class="navbar-inner">
            <div class="container-fluid">
                <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                </a>
                <a class="brand" href="#">{{ $site_name }}</a>
                <div class="nav-collapse collapse">
                @if (isset($menus))
                {{ $menus }}
                @endif
                </div>
                {{-- /.nav-collapse --}}
                <ul class="nav pull-right">
                    <li class="divider-vertical"></li>
@if (\Auth::user())
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            &nbsp;{{\Auth::user()->email}} <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="#">Profile</a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="{{\URL::base()}}/user/logout">Logout</a>
                            </li>
                        </ul>
                    </li>
@else
                    <li id="menu_login">
                        <a href="{{\URL::base()}}/user/login"><i class="icon-user icon-white"></i> Login</a>
                    </li>
@endif
                </ul>
            </div>
        </div>
    </div>
