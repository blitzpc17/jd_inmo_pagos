<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sistema' }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>
        :root{
            --sidebar-w: 270px;
            --primary: #07598C;
            --primary-2: #03658C;
            --bg: #f4f6f9;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #dbe3ea;
        }

        body{
            background: var(--bg);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, sans-serif;
        }

        .app-shell{
            display:flex;
            min-height:100vh;
        }

        .sidebar{
            width:var(--sidebar-w);
            background:linear-gradient(180deg,var(--primary),var(--primary-2));
            color:#fff;
            position:fixed;
            top:0;
            left:0;
            bottom:0;
            z-index:1040;
            display:flex;
            flex-direction:column;
            box-shadow: 0 0 20px rgba(0,0,0,.12);
        }

        .sidebar-brand{
            padding:20px 18px;
            font-weight:800;
            font-size:1.15rem;
            border-bottom:1px solid rgba(255,255,255,.12);
        }

        .sidebar-user{
            padding:14px 18px;
            border-bottom:1px solid rgba(255,255,255,.12);
            font-size:.92rem;
        }

        .sidebar-menu{
            padding:14px 10px;
            overflow:auto;
            flex:1;
        }

        .menu-item{
            display:block;
            width:100%;
            color:#fff;
            text-decoration:none;
            border-radius:12px;
            padding:10px 12px;
            margin-bottom:6px;
            transition:.2s ease;
        }

        .menu-item:hover,
        .menu-item.active{
            background:rgba(255,255,255,.14);
            color:#fff;
        }

        .menu-parent{
            font-size:.83rem;
            text-transform:uppercase;
            opacity:.82;
            margin:16px 10px 8px;
            letter-spacing:.03em;
        }

        .main{
            margin-left:var(--sidebar-w);
            width:calc(100% - var(--sidebar-w));
            min-height:100vh;
        }

        .topbar{
            background:#fff;
            border-bottom:1px solid var(--border);
            padding:14px 20px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            position:sticky;
            top:0;
            z-index:1030;
        }

        .content-wrap{
            padding:24px;
        }

        .page-card{
            background:var(--card);
            border:1px solid var(--border);
            border-radius:18px;
            padding:18px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .04);
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select{
            border-radius:10px !important;
            border:1px solid #ced4da !important;
            min-height:38px;
        }

        table.dataTable thead th{
            background:#eef5fb !important;
            color:#0f172a !important;
            border-bottom:1px solid #d5e3f0 !important;
            white-space:nowrap;
        }

        .modal-content{
            border-radius:18px;
            border:none;
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

        .select2-container--bootstrap4 .select2-selection{
            min-height:38px !important;
            border-radius:10px !important;
            border:1px solid #ced4da !important;
        }

        .select2-container--bootstrap4.select2-container--focus .select2-selection{
            border-color:#86b7fe !important;
            box-shadow:0 0 0 .2rem rgba(13,110,253,.15) !important;
        }

        .select2-dropdown{
            border:1px solid #ced4da !important;
            border-radius:10px !important;
            z-index:9999 !important;
        }

        .select2-results__option{
            color:#111 !important;
        }

        .form-control,
        .form-select{
            border-radius:10px;
        }

        .badge-soft{
            background:#eaf4fb;
            color:#07598C;
            padding:.45rem .65rem;
            border-radius:999px;
            font-weight:600;
        }

        @media (max-width: 991px){
            .sidebar{
                position:fixed;
                left:-100%;
                transition:.25s ease;
            }
            .sidebar.show{
                left:0;
            }
            .main{
                margin-left:0;
                width:100%;
            }
        }

        .overlay-mobile{
            display:none;
        }

        @media (max-width: 991px){
            .overlay-mobile.show{
                display:block;
                position:fixed;
                inset:0;
                background:rgba(15,23,42,.35);
                z-index:1035;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
<div class="overlay-mobile" id="overlayMobile"></div>

<div class="app-shell">
    <aside class="sidebar" id="sidebarApp">
        <div class="sidebar-brand">
            LOTIFICACIONES
        </div>

        <div class="sidebar-user">
            <div class="fw-bold">{{ session('auth_user.nombre') }}</div>
            <div class="small opacity-75">{{ session('auth_user.role_name') }}</div>
        </div>

        <div class="sidebar-menu">
            <div class="menu-parent">General</div>
            <a href="{{ route('dashboard') }}" class="menu-item">
                <i class="fa-solid fa-gauge-high me-2"></i> Dashboard
            </a>

            <div class="menu-parent">Catálogos</div>
            <a href="{{ route('catalogos.index', 'processes') }}" class="menu-item">Procesos</a>
            <a href="{{ route('catalogos.index', 'statuses') }}" class="menu-item">Estados</a>
            <a href="{{ route('catalogos.index', 'positions') }}" class="menu-item">Puestos</a>
            <a href="{{ route('catalogos.index', 'roles') }}" class="menu-item">Roles</a>
            <a href="{{ route('catalogos.index', 'charge_types') }}" class="menu-item">Tipos de cobro</a>
            <a href="{{ route('catalogos.index', 'contract_payment_types') }}" class="menu-item">Tipos pago contrato</a>
            <a href="{{ route('catalogos.index', 'offices') }}" class="menu-item">Oficinas</a>
            <a href="{{ route('catalogos.index', 'payment_methods') }}" class="menu-item">Formas de pago</a>
            <a href="{{ route('catalogos.index', 'partners') }}" class="menu-item">Socios</a>
            <a href="{{ route('catalogos.index', 'menus') }}" class="menu-item">Menú</a>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-secondary d-lg-none" id="btnToggleSidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="fw-semibold">{{ $title ?? 'Sistema' }}</div>
            </div>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="btn btn-outline-danger btn-sm">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Salir
                </button>
            </form>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const sidebarApp = document.getElementById('sidebarApp');
    const overlayMobile = document.getElementById('overlayMobile');
    const btnToggleSidebar = document.getElementById('btnToggleSidebar');

    if(btnToggleSidebar){
        btnToggleSidebar.addEventListener('click', function(){
            sidebarApp.classList.toggle('show');
            overlayMobile.classList.toggle('show');
        });
    }

    if(overlayMobile){
        overlayMobile.addEventListener('click', function(){
            sidebarApp.classList.remove('show');
            overlayMobile.classList.remove('show');
        });
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });
</script>

@stack('scripts')
</body>
</html>