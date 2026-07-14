@extends('layouts.app')
@section('title', 'Log in')

@section('content')
    <h1>Log in</h1>

    <form method="post" action="{{ route('login') }}">
        @csrf

        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email">
        <input type="password" name="password" placeholder="Password">
        <label><input type="checkbox" name="remember" value="1"> Remember me</label>

        @error('email') <p role="alert">{{ $message }}</p> @enderror

        <button type="submit">Log in</button>
    </form>
@endsection
