<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <h1>This is a tests view</h1>

    @if(isset($text))
    <div>{{ $text }}</div>
    @endif

    @if(isset($user))
    <div>Email: {{ $user->email }}</div>
    @endif
</body>
</html>