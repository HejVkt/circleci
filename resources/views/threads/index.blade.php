@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                @include('threads._list')

                {{ $threads->links() }}
            </div>

            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Thrending Threads
                    </div>
                    <div class="panel-body">
                        <ul class="list-group">
                            @foreach($trends as $trend)
                                <li class="list-group-item">
                                    <a href="{{ $trend->url }}">
                                        {{ $trend->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
