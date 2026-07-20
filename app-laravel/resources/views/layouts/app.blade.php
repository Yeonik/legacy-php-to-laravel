<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- F-03: {{ }} escapes. The legacy template echoed $article['title'] raw. --}}
    <title>@yield('title', 'Blog')</title>
    {{-- One static, self-contained stylesheet served from public/. No Vite,
         no Tailwind build — this is a backend/security case study. --}}
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif

    @yield('content')
</body>
</html>
