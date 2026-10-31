@extends('Template::layouts.app')
@section('app')
    @include('Template::partials.user_header')

    @if (!request()->routeIs('home'))
        @include('Template::partials.breadcrumb')
    @endif

    <section class="py-100">
        <div class="container">
            @yield('content')
        </div>
    </section>


    @include('Template::partials.footer')
@endsection

@push('script')
    <script>
        "use strict";
        $(document).on('click touchstart', function(e) {
            $('.win-loss-popup').removeClass('active');
        });
    </script>
@endpush
