@forelse($threads as $thread)
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="level">

                <div class="flex">
                    <h4>
                        @if(auth()->check() && $thread->hasUpdatedFor(auth()->user()))
                            <strong><a href="{{ $thread->path() }}">{{ $thread->title }}</a></strong>
                        @else
                            <a href="{{ $thread->path() }}">{{ $thread->title }}</a>
                        @endif
                    </h4>
                    <div>created by <a href="{{ route('profile', $thread->creator->name) }}">{{ $thread->creator->name }}</a></div>
                </div>

                <a href="{{ $thread->path() }}">{{ $thread->replies_count }} {{ str_plural('reply', $thread->replies_count ) }}</a>
            </div>
        </div>
        <div class="panel-body">
            <article>
                <div class="body">{{ $thread->body }}</div>
            </article>
        </div>
        <div class="panel-footer">
            {{ $thread->visits }} Visits
        </div>
    </div>
@empty
    There are no relevant result at this time
@endforelse