@extends('layouts.app')
@section('title', 'Articles')

@section('content')
    <h1>Articles</h1>

    <form method="get" action="{{ route('articles.index') }}">
        {{-- F-03: the legacy version echoed $_GET['q'] straight back into the
             value attribute. Here Blade escapes it. --}}
        <input type="text" name="q" value="{{ $term }}">
        <button type="submit">Search</button>
    </form>

    @forelse ($articles as $article)
        <article>
            <h2>
                <a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a>
            </h2>
            <small>{{ $article->created_at->format('d M Y') }}</small>
        </article>
    @empty
        <p>Nothing found.</p>
    @endforelse

    {{-- F-12: the legacy listing loaded the entire table on every request. --}}
    {{ $articles->links() }}
@endsection
