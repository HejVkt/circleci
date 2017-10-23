<!-- Left Side Of Navbar -->
<ul class="nav navbar-nav">

    <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
           aria-expanded="false">
            Browse <span class="caret"></span>
        </a>

        <ul class="dropdown-menu">
            <li><a href="/threads">All Threads</a></li>

            <li><a href="/threads?popular=1">Popular Threads</a></li>
            <li><a href="/threads?unanswered=1">Unanswered Threads</a></li>

            @if (auth()->check())
                <li><a href="/threads?by={{ auth()->user()->name }}">My Threads</a></li>
            @endif
        </ul>
    </li>

    &nbsp;<li><a href="/threads/create">Create thread</a></li>

    <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
           aria-expanded="false">Channels <span class="caret"></span></a>
        <ul class="dropdown-menu">
            @foreach(\App\Channel::all() as $channel)
                <li><a href="/threads/{{ $channel->slug }}">{{ $channel->name }}</a></li>
            @endforeach
        </ul>
    </li>

</ul>

<!-- Right Side Of Navbar -->
<ul class="nav navbar-nav navbar-right">
    <!-- Authentication Links -->
    @if (Auth::guest())
        <li><a href="{{ route('login') }}">Login</a></li>
        <li><a href="{{ route('register') }}">Register</a></li>
    @else

        <user-notifications></user-notifications>

        <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
               aria-expanded="false">
                {{ Auth::user()->name }} <span class="caret"></span>
            </a>

            <ul class="dropdown-menu" role="menu">

                <li><a href="{{ route('profile', auth()->user()) }}">My Profile</a></li>

                <li>
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                        Logout
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                          style="display: none;">
                        {{ csrf_field() }}
                    </form>
                </li>
            </ul>
        </li>
    @endif
</ul>