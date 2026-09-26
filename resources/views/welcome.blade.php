<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TheapKa Online — API</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090b10;
            --card-bg: rgba(22, 27, 34, 0.7);
            --border: rgba(255, 255, 255, 0.08);
            --primary: #f43f5e;
            --primary-glow: rgba(244, 63, 94, 0.25);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --emerald: #10b981;
            --emerald-glow: rgba(16, 185, 129, 0.2);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg);
            background-image: 
                radial-gradient(at 15% 15%, rgba(244, 63, 94, 0.12) 0px, transparent 50%),
                radial-gradient(at 85% 85%, rgba(99, 102, 241, 0.10) 0px, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .container {
            width: 100%;
            max-width: 680px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            backdrop-filter: blur(16px);
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo-box {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #f43f5e, #be123c);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 16px var(--primary-glow);
            font-size: 22px;
        }

        .brand-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .brand-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: var(--emerald-glow);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            color: #34d399;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background-color: var(--emerald);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--emerald);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.9); }
        }

        .description {
            font-size: 15px;
            line-height: 1.6;
            color: var(--text-muted);
            margin-bottom: 28px;
        }

        .grid-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 28px;
        }

        .link-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            text-decoration: none;
            color: var(--text-main);
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .link-card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.16);
            transform: translateY(-2px);
        }

        .link-title {
            font-size: 14px;
            font-weight: 600;
        }

        .link-subtitle {
            font-size: 12px;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
        }

        .meta-bar {
            border-top: 1px solid var(--border);
            padding-top: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
            flex-wrap: wrap;
            gap: 10px;
        }

        .meta-bar span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="brand">
                <div class="logo-box">💍</div>
                <div>
                    <h1 class="brand-title">TheapKa Online</h1>
                    <p class="brand-subtitle">Digital Wedding Platform API Service</p>
                </div>
            </div>
            <div class="status-badge">
                <span class="status-dot"></span>
                <span>Operational</span>
            </div>
        </div>

        <p class="description">
            TheapKa REST API backend is active and serving endpoints for the couple portal, administrative control, and public guest wedding invitations.
        </p>

        <div class="grid-links">
            <a href="http://localhost:5173" class="link-card" target="_blank" rel="noopener">
                <div class="link-title">Couple Portal ↗</div>
                <div class="link-subtitle">:5173 / theapka-user</div>
            </a>
            <a href="http://localhost:5174" class="link-card" target="_blank" rel="noopener">
                <div class="link-title">Admin Console ↗</div>
                <div class="link-subtitle">:5174 / theapka-admin</div>
            </a>
            <a href="/api/docs.yaml" class="link-card" target="_blank" rel="noopener">
                <div class="link-title">OpenAPI Spec ↗</div>
                <div class="link-subtitle">/api/docs.yaml</div>
            </a>
        </div>

        <div class="meta-bar">
            <span>Laravel v{{ Illuminate\Foundation\Application::VERSION }}</span>
            <span>PHP v{{ PHP_VERSION }}</span>
            <span>{{ now()->toDateTimeString() }}</span>
        </div>
    </div>
</body>
</html>
