@extends('Template::layouts.app')
@section('app')
    @include('Template::partials.header')

    @if (!request()->routeIs('home'))
        @include('Template::partials.breadcrumb')
    @endif

    @yield('content')

    @include('Template::partials.footer')
@endsection
