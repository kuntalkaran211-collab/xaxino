@extends('Template::layouts.frontend')
@section('content')

    <section class="games-section pt-100 pb-50">
        <div class="container">
            <div class="games-section-wrapper">
                @include('Template::partials.game', ['games' => $games])
            </div>
        </div>
    </section>

    @if (@$sections->secs != null)
        @foreach (json_decode($sections->secs) as $sec)
            @include('Template::sections.' . $sec)
        @endforeach
    @endif
@endsection
