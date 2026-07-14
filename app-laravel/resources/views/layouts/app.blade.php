<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- F-03: {{ }} escapes. The legacy template echoed $article['title'] raw. --}}
    <title>@yield('title', 'Blog')</title>
</head>
<body>
    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif

    @yield('content')
</body>
</html>
