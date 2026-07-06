<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Sistema' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/jstree@3.3.16/dist/themes/default/style.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('/assets/css/jd-theme.css') }}">

    <script>
        // Prevent FOUC by setting theme immediately
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>

    <style>
        /* Light Theme Variables (Default) */
        :root {
            --sidebar-w: 280px;
            --primary: #3b82f6; 
            --primary-2: #8b5cf6; 
            --bg: #f8fafc; 
            --card: #ffffff; 
            --text: #0f172a; 
            --muted: #64748b; 
            --border: #e2e8f0; 
            --hover-bg: rgba(15, 23, 42, 0.04);
            --sidebar-bg: rgba(255, 255, 255, 0.85);
            --topbar-bg: rgba(255, 255, 255, 0.85);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --focus-ring: 0 0 0 4px rgba(59, 130, 246, 0.15);
            --input-bg: #ffffff;
            --glass-border: rgba(0,0,0,0.05);
        }

        /* Dark Theme Variables */
        [data-bs-theme="dark"] {
            --bg: #0f172a; 
            --card: #1e293b; 
            --text: #f8fafc; 
            --muted: #94a3b8; 
            --border: #334155; 
            --hover-bg: rgba(255, 255, 255, 0.05);
            --sidebar-bg: rgba(15, 23, 42, 0.75);
            --topbar-bg: rgba(15, 23, 42, 0.85);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.3);
            --shadow-lg: 0 10px 25px -3px rgb(0 0 0 / 0.5);
            --focus-ring: 0 0 0 4px rgba(56, 189, 248, 0.2);
            --input-bg: #0f172a;
            --glass-border: rgba(255,255,255,0.08);
        }

        /* Base Pro Styling */
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        h1, h2, h3, h4, h5, h6, .sidebar-brand, .topbar .fw-semibold {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.02em;
        }

        .app-shell {
            min-height: 100vh;
            display: flex;
        }

        /* Pro Sidebar */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid var(--border);
            color: var(--text);
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1045;
            transform: translateX(-100%);
            transition: transform .3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.3s ease, border-color 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: 20px 0 40px rgba(0,0,0,0.05);
        }

        [data-bs-theme="dark"] .sidebar {
            box-shadow: 20px 0 40px rgba(0,0,0,0.25);
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .sidebar-brand {
            padding: 24px 24px 20px;
            font-size: 1.5rem;
            font-weight: 800;
            color: #D9042B; /* Brand Red */
            border-bottom: 1px solid var(--border);
        }
        
        [data-bs-theme="dark"] .sidebar-brand {
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
        }

        .sidebar-user {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-user .fw-bold {
            font-size: 1.05rem;
        }

        .sidebar-menu {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 24px 16px;
        }

        .sidebar-menu::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-menu::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        .menu-group {
            margin-bottom: 20px;
        }

        .menu-parent-link,
        .menu-child-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--muted);
            text-decoration: none;
            border-radius: 10px;
            padding: 10px 14px;
            transition: all .2s ease;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .menu-parent-link i,
        .menu-child-link i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
            transition: color 0.2s ease;
        }

        .menu-parent-link:hover,
        .menu-child-link:hover {
            background: var(--hover-bg);
            color: var(--text);
            transform: translateX(4px);
        }

        .menu-parent-link.active,
        .menu-child-link.active {
            background: var(--hover-bg);
            color: var(--primary);
            font-weight: 600;
            box-shadow: inset 3px 0 0 var(--primary);
        }

        [data-bs-theme="dark"] .menu-parent-link.active,
        [data-bs-theme="dark"] .menu-child-link.active {
            color: #38bdf8;
            box-shadow: inset 3px 0 0 #38bdf8;
            background: linear-gradient(90deg, rgba(56,189,248,0.1), transparent);
        }

        .menu-group-title {
            padding: 4px 14px 10px;
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--muted);
            letter-spacing: .08em;
        }

        .menu-children {
            margin-left: 24px;
            border-left: 1px solid var(--border);
            padding-left: 12px;
            margin-top: 4px;
        }

        .main {
            width: 100%;
            min-height: 100vh;
        }

        /* Pro Topbar */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 24px;
            background: var(--topbar-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1030;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .topbar .fw-semibold {
            font-size: 1.3rem;
            margin-left: 8px;
        }

        .content-wrap {
            padding: 32px 24px;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Pro Card */
        .page-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            padding: 28px;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .overlay-mobile {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            z-index: 1040;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .overlay-mobile.show {
            display: block;
            opacity: 1;
        }

        .btn-hamburger, .btn-theme-toggle {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 10px;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .btn-hamburger:hover, .btn-theme-toggle:hover {
            background: var(--hover-bg);
            color: var(--primary);
            border-color: var(--primary);
        }

        /* Pro Inputs */
        .form-control, .form-select {
            background-color: var(--input-bg);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }
        .form-control:focus, .form-select:focus {
            background-color: var(--input-bg);
            border-color: var(--primary);
            box-shadow: var(--focus-ring);
            color: var(--text);
        }
        .form-control::placeholder {
            color: var(--muted);
        }
        .input-group-text {
            background-color: var(--hover-bg);
            border: 1px solid var(--border);
            color: var(--muted);
            border-radius: 10px;
        }

        /* Pro Select2 Overrides */
        .select2-container--bootstrap4 .select2-selection {
            background-color: var(--input-bg) !important;
            border: 1px solid var(--border) !important;
            color: var(--text) !important;
            border-radius: 10px !important;
            min-height: 42px !important;
            display: flex;
            align-items: center;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }
        .select2-container--bootstrap4 .select2-selection__rendered {
            color: var(--text) !important;
            padding-left: 16px !important;
        }
        .select2-container--bootstrap4.select2-container--focus .select2-selection {
            border-color: var(--primary) !important;
            box-shadow: var(--focus-ring) !important;
        }
        .select2-dropdown {
            background-color: var(--card) !important;
            border: 1px solid var(--border) !important;
            border-radius: 12px !important;
            box-shadow: var(--shadow-lg) !important;
            z-index: 9999 !important;
            padding: 4px;
        }
        .select2-container--bootstrap4 .select2-results__option {
            color: var(--text);
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 2px;
            font-size: 0.95rem;
        }
        .select2-container--bootstrap4 .select2-results__option[aria-selected="true"] {
            background-color: var(--hover-bg) !important;
            color: var(--primary) !important;
            font-weight: 500;
        }
        .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary) !important;
            color: #fff !important;
        }
        .select2-search__field {
            background-color: var(--input-bg) !important;
            border: 1px solid var(--border) !important;
            color: var(--text) !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
        }

        /* Pro Modals */
        .modal-content {
            background-color: var(--card);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        .modal-header {
            background: var(--hover-bg);
            color: var(--text);
            border-bottom: 1px solid var(--border);
            padding: 20px 24px;
        }
        .modal-footer {
            background: var(--hover-bg);
            border-top: 1px solid var(--border);
            padding: 20px 24px;
        }
        .btn-close {
            transition: transform 0.2s;
        }
        [data-bs-theme="dark"] .btn-close {
            filter: invert(1);
        }
        .btn-close:hover {
            transform: scale(1.1);
        }

        /* Pro DataTables */
        table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
        }
        table.dataTable thead th {
            white-space: nowrap;
            background: var(--hover-bg) !important;
            color: var(--muted) !important;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border) !important;
            padding: 16px !important;
        }
        table.dataTable tbody td {
            background: transparent !important;
            border-bottom: 1px solid var(--border) !important;
            padding: 16px !important;
            vertical-align: middle;
            color: var(--text);
            font-size: 0.95rem;
        }
        table.dataTable tbody tr {
            transition: background-color 0.2s ease;
        }
        table.dataTable tbody tr:hover td {
            background: var(--hover-bg) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            padding: 6px 12px !important;
            margin: 0 2px !important;
            border: 1px solid transparent !important;
            color: var(--text) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--hover-bg) !important;
            border-color: var(--border) !important;
            color: var(--text) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #fff !important;
            box-shadow: var(--shadow-sm);
        }
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            background-color: var(--input-bg);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 8px;
            padding: 6px 12px;
            transition: all 0.2s;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--primary);
            box-shadow: var(--focus-ring);
            outline: none;
        }

        .jstree-default .jstree-anchor {
            line-height: 28px;
            color: var(--text);
            border-radius: 6px;
            transition: all 0.2s;
        }
        .jstree-default .jstree-hovered {
            background: var(--hover-bg);
        }
        .jstree-default .jstree-clicked {
            background: var(--primary);
            color: #fff;
        }

        .drawer-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
        }

        /* Buttons Global */
        .btn {
            border-radius: 10px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.2s ease;
        }
    </style>

    @stack('styles')
</head>
<body>
<div class="overlay-mobile" id="drawerOverlay"></div>

<div class="app-shell">
    <aside class="sidebar" id="appSidebar">
        <div class="sidebar-brand">JD INMOBILIARIA</div>

        <div class="sidebar-user">
            <div class="fw-bold">{{ session('auth_user.nombre') }}</div>
            <div class="small text-muted">{{ session('auth_user.role_name') }}</div>
        </div>

        <div class="sidebar-menu">
            @forelse(($dynamicMenu ?? []) as $item)
                <div class="menu-group">
                    @if(empty($item['children']))
                        <a href="{{ !empty($item['ruta']) ? url($item['ruta']) : 'javascript:void(0)' }}"
                            class="menu-parent-link {{ !empty($item['ruta']) && request()->is(trim($item['ruta'], '/').'*') ? 'active' : '' }}">
                            <i class="{{ $item['icono'] ?: 'fa-solid fa-circle' }}"></i>
                            <span>{{ $item['nombre'] }}</span>
                        </a>
                    @else
                        <div class="menu-group-title">{{ $item['nombre'] }}</div>
                        <div class="menu-children">
                            @foreach($item['children'] as $child)
                               <a href="{{ !empty($child['ruta']) ? url($child['ruta']) : 'javascript:void(0)' }}"
                                    class="menu-child-link {{ !empty($child['ruta']) && request()->is(trim($child['ruta'], '/').'*') ? 'active' : '' }}">
                                    <i class="{{ $child['icono'] ?: 'fa-solid fa-circle-dot' }}"></i>
                                    <span>{{ $child['nombre'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-muted small px-3 py-2">Sin módulos asignados</div>
            @endforelse
        </div>

        <div class="drawer-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="btn btn-outline-danger w-100">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Salir
                </button>
            </form>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn-hamburger" id="btnHamburger" type="button">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="fw-semibold">{{ $title ?? 'Sistema' }}</div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <button class="btn-theme-toggle" id="btnThemeToggle" type="button">
                    <i class="fa-solid fa-moon dark-icon"></i>
                    <i class="fa-regular fa-sun light-icon d-none"></i>
                </button>
                <div class="small text-muted fw-medium">{{ session('auth_user.alias') }}</div>
            </div>
        </div>

        <div class="content-wrap">
            @yield('content')
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jstree@3.3.16/dist/jstree.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    const appSidebar = document.getElementById('appSidebar');
    const drawerOverlay = document.getElementById('drawerOverlay');
    const btnHamburger = document.getElementById('btnHamburger');
    const btnThemeToggle = document.getElementById('btnThemeToggle');
    const htmlEl = document.documentElement;

    function openDrawer(){
        appSidebar.classList.add('show');
        drawerOverlay.classList.add('show');
    }

    function closeDrawer(){
        appSidebar.classList.remove('show');
        drawerOverlay.classList.remove('show');
    }

    btnHamburger.addEventListener('click', function(){
        if(appSidebar.classList.contains('show')){
            closeDrawer();
        }else{
            openDrawer();
        }
    });

    drawerOverlay.addEventListener('click', closeDrawer);

    // Theme Toggle Logic
    function updateThemeIcons() {
        const currentTheme = htmlEl.getAttribute('data-bs-theme');
        if(currentTheme === 'dark') {
            btnThemeToggle.querySelector('.dark-icon').classList.add('d-none');
            btnThemeToggle.querySelector('.light-icon').classList.remove('d-none');
        } else {
            btnThemeToggle.querySelector('.light-icon').classList.add('d-none');
            btnThemeToggle.querySelector('.dark-icon').classList.remove('d-none');
        }
    }
    
    updateThemeIcons();

    btnThemeToggle.addEventListener('click', function() {
        const currentTheme = htmlEl.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        htmlEl.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcons();
    });

</script>

@stack('scripts')
</body>
</html>