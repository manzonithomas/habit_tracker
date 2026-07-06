<?php
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#0a0a0a">
    <style>
        :root {
            --bg: #0a0a0a;
            --card: #1c1c1e;
            --primary: #30d158;
            --danger: #ff453a;
            --text: #e0e0e0;
            --muted: #666;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            text-align: center;
        }

        .card {
            background: var(--card);
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 350px;
            width: 100%;
        }

        .icon {
            font-size: 4em;
            margin-bottom: 20px;
            display: block;
        }

        h1 {
            font-size: 1.3em;
            color: white;
            margin-bottom: 12px;
            font-weight: 600;
        }

        p {
            color: var(--muted);
            font-size: 0.95em;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .btn-row {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        a.btn-primary {
            display: block;
            padding: 14px;
            background: var(--primary);
            color: #000;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s;
        }

        a.btn-secondary {
            display: block;
            padding: 14px;
            background: transparent;
            color: var(--muted);
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            border: 1px solid var(--border, #333);
            transition: opacity 0.2s;
        }

        a:active {
            opacity: 0.8;
        }

        .tech {
            margin-top: 20px;
            font-size: 0.75em;
            color: #444;
            word-break: break-all;
            background: var(--bg);
            padding: 10px;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="card">
        <span class="icon">🔧</span>
        <h1>Ops, qualcosa si è inceppato</h1>
        <p>Non riesco a connettermi ai server al momento.
            Potrebbe essere un problema temporaneo, riprova tra poco!</p>
        <div class="btn-row">
            <a href="javascript:void(0)" onclick="setTimeout(()=>window.location.reload(), 100)" class="btn-primary"
                style="display:block;margin-top:0;">Riprova ora</a>
        </div>
    </div>
</body>

</html>