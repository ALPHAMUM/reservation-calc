<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --success: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            width: 100%;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            background: var(--glass-bg);
            border: 1px solid var(--border);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(to right, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            border: 1px solid transparent;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.2);
        }

        footer {
            margin-top: auto;
            padding: 2rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="container">
        <header class="animate-in">
            <div class="logo">Reservation</div>
            <nav style="display: flex; gap: 1rem;">
                <a href="{{ route('dashboard') }}" class="btn" style="color: var(--text)">Dashboard</a>
                <a href="{{ url('/setup') }}" class="btn" style="color: var(--text); border: 1px solid var(--border);">Setup</a>
            </nav>
        </header>

        <main>
            @if(session('error') || (isset($error) && $error))
                <div class="alert alert-error animate-in">
                    {{ session('error') ?? $error }}
                </div>
            @endif

            @yield('content')
        </main>

        <footer>
            &copy; {{ date('Y') }} Alphaland Corporation All Rights Reserved
        </footer>
    </div>
    @yield('modals')
    @yield('scripts')
</body>
</html>
