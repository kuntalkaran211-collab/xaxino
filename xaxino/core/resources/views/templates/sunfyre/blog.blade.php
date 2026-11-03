@extends('Template::layouts.frontend')
@section('content')

    <section class="blog py-100">
        <div class="container">
            <div class="blog-wrapper">
                @include('Template::partials.blog')
            </div>
            {{ paginateLinks($blogs) }}
        </div>
    </section>

    @if (@$sections->secs != null)
        @foreach (json_decode($sections->secs) as $sec)
            @include('Template::sections.' . $sec)
        @endforeach
    @endif

@endsection
