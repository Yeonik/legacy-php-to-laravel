@extends('layouts.app')
@section('title', $article->title)

@section('content')
    <h1>{{ $article->title }}</h1>
    <p><small>Views: {{ $article->views }}</small></p>

    @if ($article->cover_path)
        <img src="{{ route('covers.show', $article) }}" alt="">
    @endif

    {{--
        The ONE place in this application that renders unescaped HTML.

        It is deliberate: an article body is authored content and needs
        formatting. It is safe only because the body is sanitised server-side
        through an allow-list purifier before it is ever stored.

        Do not copy this pattern to comments, titles, or anything a visitor can
        submit. In the legacy app every one of those was rendered exactly like
        this — which is how a single comment became stored XSS for every
        visitor, including the admin. See F-03.
    --}}
    <div class="prose">{!! $article->body !!}</div>

    <h2>Comments</h2>

    @foreach ($article->comments as $comment)
        <div class="comment">
            {{-- escaped, unlike the legacy version --}}
            <strong>{{ $comment->author }}</strong>
            <p>{{ $comment->body }}</p>
        </div>
    @endforeach

    <h3>Leave a comment</h3>
    <form method="post" action="{{ route('comments.store', $article) }}">
        @csrf   {{-- F-02: the legacy app had no CSRF token on any form --}}

        <input type="text" name="author" value="{{ old('author') }}" placeholder="Your name">
        @error('author') <span>{{ $message }}</span> @enderror

        <textarea name="body">{{ old('body') }}</textarea>
        @error('body') <span>{{ $message }}</span> @enderror

        <button type="submit">Send</button>
    </form>
@endsection
