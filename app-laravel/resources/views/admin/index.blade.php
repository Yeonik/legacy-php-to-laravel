@extends('layouts.app')
@section('title', 'Admin — Articles')

@section('content')
    <h1>Articles</h1>

    <table>
        @foreach ($articles as $article)
            <tr>
                <td>{{ $article->id }}</td>
                <td>{{ $article->title }}</td>
                <td>
                    @can('delete', $article)
                        {{--
                            F-02 / F-10: in the legacy panel this was
                                <a href="admin.php?action=delete&id=7">delete</a>
                            — a destructive write behind a GET link with no
                            token. Any page that could make the admin's browser
                            issue a request could delete content.
                        --}}
                        <form method="post" action="{{ route('admin.articles.destroy', $article) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Delete</button>
                        </form>
                    @endcan
                </td>
            </tr>
        @endforeach
    </table>

    {{ $articles->links() }}

    @can('create', App\Models\Article::class)
        <h2>New article</h2>
        <form method="post" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="text" name="title" value="{{ old('title') }}" placeholder="Title">
            @error('title') <span>{{ $message }}</span> @enderror

            <textarea name="body">{{ old('body') }}</textarea>
            @error('body') <span>{{ $message }}</span> @enderror

            <input type="file" name="cover" accept="image/*">
            @error('cover') <span>{{ $message }}</span> @enderror

            <label><input type="checkbox" name="published" value="1"> Publish</label>

            <button type="submit">Save</button>
        </form>
    @endcan
@endsection
