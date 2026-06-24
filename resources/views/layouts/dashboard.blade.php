<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'WX Agency') }}</title>
    <link rel="shortcut icon" href="https://www.wx.agency/hubfs/WX.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0a0a0f;
            --surface:   #0f0f1a;
            --surface2:  #14141f;
            --border:    rgba(255,255,255,0.07);
            --indigo:    #6366f1;
            --indigo-d:  #4f46e5;
            --indigo-soft: rgba(99,102,241,0.12);
            --text:      #e2e8f0;
            --muted:     #64748b;
            --sidebar-w: 240px;
            --topbar-h:  60px;
        }

        html, body { height: 100%; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            overflow: hidden;
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 200;
            transition: transform 0.25s ease;
        }

        .sidebar-logo {
            height: var(--topbar-h);
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 20px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .sidebar-logo img { width: 28px; height: 28px; }

        .sidebar-logo span {
            font-size: 0.88rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 12px 10px;
        }

        .nav-label {
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 14px 10px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #94a3b8;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .nav-item:hover { background: var(--indigo-soft); color: var(--text); }
        .nav-item.active { background: var(--indigo-soft); color: #fff; }
        .nav-item.active svg { color: var(--indigo); }

        .nav-item svg { width: 16px; height: 16px; flex-shrink: 0; }

        /* submenu */
        .nav-submenu { padding-left: 38px; overflow: hidden; display: none; }
        .nav-submenu.open { display: block; }
        .nav-submenu .nav-item { font-size: 0.82rem; padding: 7px 10px; }

        .nav-chevron {
            margin-left: auto;
            width: 14px; height: 14px;
            transition: transform 0.2s;
        }

        .nav-parent.open .nav-chevron { transform: rotate(90deg); }

        /* ── Topbar ── */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: var(--topbar-h);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 100;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .hamburger {
            display: none;
            background: none;
            border: none;
            color: var(--text);
            cursor: pointer;
            padding: 4px;
        }

        .page-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #fff;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Profile dropdown */
        .profile-wrap { position: relative; }

        .profile-btn {
            display: flex;
            align-items: center;
            gap: 9px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 6px 12px 6px 6px;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .profile-btn:hover { border-color: rgba(99,102,241,0.4); }

        .avatar {
            width: 30px; height: 30px;
            border-radius: 8px;
            background: var(--indigo);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .profile-name {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text);
        }

        .profile-chevron { width: 14px; height: 14px; color: var(--muted); }

        .profile-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 12px;
            min-width: 200px;
            padding: 6px;
            display: none;
            box-shadow: 0 16px 48px rgba(0,0,0,0.5);
            z-index: 300;
        }

        .profile-dropdown.open { display: block; }

        .profile-dropdown-header {
            padding: 10px 12px 8px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 6px;
        }

        .profile-dropdown-header .name {
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
        }

        .profile-dropdown-header .email {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .dropdown-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.82rem;
            color: #94a3b8;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .dropdown-link:hover { background: var(--indigo-soft); color: var(--text); }
        .dropdown-link.danger:hover { background: rgba(239,68,68,0.1); color: #f87171; }
        .dropdown-link svg { width: 15px; height: 15px; }

        .dropdown-divider { border: none; border-top: 1px solid var(--border); margin: 6px 0; }

        /* ── Main content ── */
        .main {
            margin-left: var(--sidebar-w);
            margin-top: var(--topbar-h);
            flex: 1;
            overflow-y: auto;
            height: calc(100vh - var(--topbar-h));
            padding: 28px 28px;
        }

        /* ── Overlay (mobile) ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 150;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay.open { display: block; }

            .topbar { left: 0; }

            .main { margin-left: 0; }

            .hamburger { display: block; }

            .profile-name { display: none; }
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
    </style>
    @stack('styles')
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="https://www.wx.agency/hubfs/WX.png" alt="WX">
        <span>WX Agency</span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>

        <a href="{{ route('home') }}" class="nav-item {{ request()->is('home') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 12L12 3l9 9M5 10v10a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1V10"/>
            </svg>
            Home
        </a>

        <div class="nav-label">Integrations</div>

        <button class="nav-item nav-parent {{ request()->is('gx/*') ? 'open active' : '' }}" onclick="toggleSubmenu(this)">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/>
            </svg>
            GX
            <svg class="nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 18l6-6-6-6"/>
            </svg>
        </button>

        <div class="nav-submenu {{ request()->is('gx/*') ? 'open' : '' }}">
            <a href="{{ route('gx.kirim') }}" class="nav-item {{ request()->is('gx/kirim*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .18h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7a2 2 0 011.72 2.03z"/>
                </svg>
                Kirim
            </a>
            <a href="#" class="nav-item">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                </svg>
                HubSpot
            </a>
        </div>

        <div class="nav-label">Webhooks</div>

        <a href="#" class="nav-item">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M18 20V10M12 20V4M6 20v-6"/>
            </svg>
            Userback
        </a>
    </nav>
</aside>

<!-- Topbar -->
<header class="topbar">
    <div class="topbar-left">
        <button class="hamburger" id="hamburgerBtn" aria-label="Toggle sidebar">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 12h18M3 6h18M3 18h18"/>
            </svg>
        </button>
        <span class="page-title">@yield('page-title', 'Dashboard')</span>
    </div>

    <div class="topbar-right">
        <div class="profile-wrap" id="profileWrap">
            <button class="profile-btn" id="profileBtn">
                <div class="avatar">{{ strtoupper(substr(Auth::user()->first_name ?? Auth::user()->name ?? 'U', 0, 2)) }}</div>
                <span class="profile-name">{{ Auth::user()->first_name ?? Auth::user()->name }}</span>
                <svg class="profile-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                    <div class="name">{{ Auth::user()->first_name ?? Auth::user()->name }}</div>
                    <div class="email">{{ Auth::user()->email }}</div>
                </div>
                <a href="{{ route('home') }}" class="dropdown-link">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z"/>
                    </svg>
                    My Profile
                </a>
                <hr class="dropdown-divider">
                <button class="dropdown-link danger" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
                    </svg>
                    Sign Out
                </button>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</header>

<!-- Main content -->
<main class="main">
    @yield('content')
</main>

<script>
    // Profile dropdown
    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    profileBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        profileDropdown.classList.toggle('open');
    });
    document.addEventListener('click', function() {
        profileDropdown.classList.remove('open');
    });

    // Sidebar toggle (mobile)
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    document.getElementById('hamburgerBtn').addEventListener('click', function() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
    });
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    });

    // Submenu toggle
    function toggleSubmenu(btn) {
        const sub = btn.nextElementSibling;
        btn.classList.toggle('open');
        sub.classList.toggle('open');
    }
</script>

<!-- Userback -->
<script>
    window.Userback = window.Userback || {};
    Userback.access_token = '3783|65108|joO0IGuxtmy9vBXjjARKh5s0WfPB00lw6wOMFeMUVGL4pwzibG';
    (function(d){var s=d.createElement('script');s.async=true;s.src='https://static.userback.io/widget/v1.js';(d.head||d.body).appendChild(s);})(document);
</script>

@stack('scripts')
</body>
</html>
