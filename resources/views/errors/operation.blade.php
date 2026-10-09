<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('tkmotors.name', 'ETS TK MOTORS') }}</title>
    </head>
    <body style="margin:0;min-height:100vh;background:#F6EED8;color:#0B1F4D;font-family:sans-serif;">
        <main style="max-width:32rem;margin:4rem auto;padding:1.5rem;">
            <h1 style="font-size:1.25rem;">{{ $message }}</h1>
            <p style="margin-top:1rem;"><a href="{{ url('/dashboard') }}" style="color:#0B1F4D;">Retour</a></p>
        </main>
    </body>
</html>
