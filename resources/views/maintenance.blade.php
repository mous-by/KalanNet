<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance en cours — KalanNet</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            padding: 24px;
        }
        .card {
            max-width: 480px;
            text-align: center;
            background: #1c2b22;
            border-radius: 16px;
            padding: 40px 32px;
            box-shadow: 0 20px 50px -20px rgba(0,0,0,.6);
        }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 {
            font-size: 22px;
            margin: 0 0 12px;
            font-family: Georgia, serif;
        }
        h1 span:nth-child(1) { color: #4ade80; }
        h1 span:nth-child(2) { color: #facc15; }
        h1 span:nth-child(3) { color: #f87171; }
        p {
            color: #a9b3a2;
            line-height: 1.6;
            margin: 0 0 20px;
            font-size: 15px;
        }
        a.login-link {
            display: inline-block;
            color: #4ade80;
            font-size: 13px;
            text-decoration: none;
            border: 1px solid rgba(74,222,128,.35);
            border-radius: 8px;
            padding: 8px 16px;
        }
        a.login-link:hover { background: rgba(74,222,128,.08); }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🛠️</div>
        <h1><span>KAL</span><span>AN</span><span>NET</span></h1>
        <p>{{ $message }}</p>
        <a class="login-link" href="{{ route('login') }}">Administrateur ? Se connecter</a>
    </div>
</body>
</html>
