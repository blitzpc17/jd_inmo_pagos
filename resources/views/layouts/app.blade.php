<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Sistema' }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/jstree@3.3.16/dist/themes/default/style.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('/assets/css/jd-theme.css') }}">

    <style>
        :root{
            --sidebar-w: 290px;
            --sidebar-collapsed: 0px;
            --primary: #07598C;
            --primary-2: #03658C;
            --bg: #f4f6f9;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #dbe3ea;
        }

        body{
            margin:0;
            background:var(--bg);
            color:var(--text);
            font-family:Inter,system-ui,-apple-system,sans-serif;
        }

        .app-shell{
            min-height:100vh;
            display:flex;
        }

        .sidebar{
            width:var(--sidebar-w);
            background:linear-gradient(180deg,var(--primary),var(--primary-2));
            color:#fff;
            position:fixed;
            inset:0 auto 0 0;
            z-index:1045;
            transform:translateX(-100%);
            transition:transform .25s ease;
            display:flex;
            flex-direction:column;
            box-shadow:0 20px 50px rgba(0,0,0,.15);
        }

        .sidebar.show{
            transform:translateX(0);
        }

        .sidebar-brand{
            padding:18px 20px;
            font-size:1.25rem;
            font-weight:800;
            border-bottom:1px solid rgba(255,255,255,.12);
        }

        .sidebar-user{
            padding:15px 20px;
            border-bottom:1px solid rgba(255,255,255,.12);
        }

        .sidebar-menu{
            flex:1;
            overflow:auto;
            padding:14px 10px 20px;
        }

        .menu-group{
            margin-bottom:14px;
        }

        .menu-parent-link,
        .menu-child-link{
            display:flex;
            align-items:center;
            gap:10px;
            color:#fff;
            text-decoration:none;
            border-radius:12px;
            padding:10px 12px;
            transition:.2s ease;
            opacity:.95;
        }

        .menu-parent-link:hover,
        .menu-parent-link.active,
        .menu-child-link:hover,
        .menu-child-link.active{
            background:rgba(255,255,255,.14);
            color:#fff;
        }

        .menu-group-title{
            padding:6px 10px 10px;
            font-size:.82rem;
            text-transform:uppercase;
            opacity:.8;
            letter-spacing:.03em;
        }

        .menu-children{
            margin-left:12px;
            border-left:1px solid rgba(255,255,255,.15);
            padding-left:10px;
        }

        .main{
            width:100%;
            min-height:100vh;
        }

        .topbar{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            padding:14px 18px;
            background:#fff;
            border-bottom:1px solid var(--border);
            position:sticky;
            top:0;
            z-index:1030;
        }

        .content-wrap{
            padding:20px;
        }

        .page-card{
            background:#fff;
            border:1px solid var(--border);
            border-radius:18px;
            box-shadow:0 8px 30px rgba(15,23,42,.04);
            padding:18px;
        }

        .overlay-mobile{
            position:fixed;
            inset:0;
            background:rgba(15,23,42,.38);
            z-index:1040;
            display:none;
        }

        .overlay-mobile.show{
            display:block;
        }

        .btn-hamburger{
            width:42px;
            height:42px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }

        .select2-container--bootstrap4 .select2-selection{
            min-height:38px !important;
            border-radius:10px !important;
        }

        .select2-dropdown{
            z-index:9999 !important;
        }

        .modal-content{
            border:none;
            border-radius:18px;
            overflow:hidden;
        }

        .modal-header{
            background:linear-gradient(180deg,#0a6aa6,#07598C);
            color:#fff;
            border:none;
        }

        .btn-close{
            filter:invert(1);
        }

        table.dataTable thead th{
            white-space:nowrap;
            background:#eef5fb !important;
            color:#0f172a !important;
        }

        .jstree-default .jstree-anchor{
            line-height:28px;
        }

        .drawer-footer{
            padding:14px;
            border-top:1px solid rgba(255,255,255,.12);
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
            <div class="small opacity-75">{{ session('auth_user.role_name') }}</div>
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
                <div class="text-white-50 small px-3 py-2">Sin módulos asignados</div>
            @endforelse
        </div>

        <div class="drawer-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="btn btn-outline-light w-100">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Salir
                </button>
            </form>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary btn-hamburger" id="btnHamburger" type="button">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="fw-semibold">{{ $title ?? 'Sistema' }}</div>
            </div>

            <div class="small text-muted">{{ session('auth_user.alias') }}</div>
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
</script>

@stack('scripts')
</body>
</html>