@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="page-header">
            <avatar-form :user="{{ $profileUser }}"></avatar-form>
        </div>

        <div class="page-body">
            @forelse($activities as $date => $activity)
                <h3 class="page-header">{{ $date }}</h3>
                @foreach($activity as $record)
                      @if (view()->exists("profile.activities.{$record->type}"))
                         @include ("profile.activities.{$record->type}", ['activity' => $record])
                      @endif
                @endforeach
            @empty
                There is no activity to this user yet.
            @endforelse
        </div>
    </div>
@endsection
