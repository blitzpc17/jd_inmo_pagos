<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>
        :root{
            --jd-primary:#D9042B;
            --jd-gray:#676767;
            --jd-blue:#0511F2;
            --jd-danger:#F20505;
            --jd-dark:#0D0D0D;
            --jd-bg:#f6f7fb;
            --jd-border:rgba(103,103,103,.20);
        }

        *{
            box-sizing:border-box;
        }

        body{
            margin:0;
            min-height:100vh;
            font-family:Inter, Arial, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(5,17,242,.06), transparent 28%),
                radial-gradient(circle at bottom left, rgba(217,4,43,.08), transparent 26%),
                var(--jd-bg);
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
            color:var(--jd-dark);
        }

        .jd-login-wrap{
            width:100%;
            max-width:460px;
        }

        .jd-login-card{
            background:#fff;
            border:1px solid var(--jd-border);
            border-radius:28px;
            box-shadow:0 24px 60px rgba(13,13,13,.10);
            overflow:hidden;
        }

        .jd-login-header{
            padding:34px 34px 22px 34px;
            text-align:center;
            background:
                linear-gradient(180deg, rgba(217,4,43,.04), rgba(5,17,242,.03));
            border-bottom:1px solid rgba(103,103,103,.10);
        }

        .jd-logo-box{
            width:100%;
            display:flex;
            justify-content:center;
            align-items:center;
            margin-bottom:16px;
            min-height:96px;
        }

        .jd-logo-box img{
            max-width:220px;
            max-height:92px;
            object-fit:contain;
            display:block;
        }

        .jd-logo-fallback{
            font-size:2rem;
            font-weight:800;
            color:var(--jd-primary);
            line-height:1.1;
            display:none;
        }

        .jd-title{
            margin:0;
            font-size:1.9rem;
            font-weight:800;
            color:var(--jd-dark);
        }

        .jd-subtitle{
            margin:8px 0 0 0;
            color:var(--jd-gray);
            font-size:.97rem;
        }

        .jd-login-body{
            padding:28px 34px 34px 34px;
        }

        .jd-label{
            font-size:.92rem;
            font-weight:700;
            color:var(--jd-dark);
            margin-bottom:8px;
        }

        .jd-control{
            min-height:54px;
            border-radius:16px;
            border:1px solid rgba(103,103,103,.22);
            padding:0 16px;
            font-size:1rem;
            transition:.2s ease;
        }

        .jd-control:focus{
            border-color:var(--jd-blue);
            box-shadow:0 0 0 .22rem rgba(5,17,242,.10);
        }

        .jd-input-wrap{
            position:relative;
        }

        .jd-toggle-pass{
            position:absolute;
            right:14px;
            top:50%;
            transform:translateY(-50%);
            border:none;
            background:transparent;
            color:var(--jd-gray);
            font-size:1rem;
            padding:4px;
            cursor:pointer;
        }

        .jd-btn-login{
            width:100%;
            min-height:54px;
            border:none;
            border-radius:16px;
            background:linear-gradient(90deg, var(--jd-primary), var(--jd-danger));
            color:#fff;
            font-weight:800;
            font-size:1rem;
            transition:.2s ease;
            box-shadow:0 14px 30px rgba(217,4,43,.20);
        }

        .jd-btn-login:hover{
            transform:translateY(-1px);
            color:#fff;
            box-shadow:0 18px 36px rgba(217,4,43,.26);
        }

        .jd-footer-note{
            margin-top:18px;
            text-align:center;
            font-size:.88rem;
            color:var(--jd-gray);
        }

        .alert{
            border-radius:14px;
            font-size:.93rem;
        }

        @media (max-width: 576px){
            body{
                padding:14px;
            }

            .jd-login-header{
                padding:26px 22px 18px 22px;
            }

            .jd-login-body{
                padding:22px;
            }

            .jd-title{
                font-size:1.6rem;
            }

            .jd-logo-box img{
                max-width:180px;
                max-height:80px;
            }
        }
    </style>
</head>
<body>
    <div class="jd-login-wrap">
        <div class="jd-login-card">
            <div class="jd-login-header">
                <div class="jd-logo-box">
                    <img
                        src="{{ asset('assets/images/logo.png') }}"
                        alt="Logo"
                        onerror="this.style.display='none';document.getElementById('jdLogoFallback').style.display='block';"
                    >
                    <div id="jdLogoFallback" class="jd-logo-fallback">JD Inmobiliaria</div>
                </div>

                <h1 class="jd-title">Iniciar sesión</h1>
                <p class="jd-subtitle">Accede al sistema con tus credenciales</p>
            </div>

            <div class="jd-login-body">
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="jd-label">Usuario</label>
                        <input
                            type="text"
                            name="alias"
                            class="form-control jd-control"
                            value="{{ old('alias') }}"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>

                    <div class="mb-3">
                        <label class="jd-label">Contraseña</label>
                        <div class="jd-input-wrap">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control jd-control"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="jd-toggle-pass" id="togglePass">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger mb-3">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <button type="submit" class="jd-btn-login">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>
                        Entrar
                    </button>
                </form>

                <div class="jd-footer-note">
                    JD Inmobiliaria
                </div>
            </div>
        </div>
    </div>

    <script>
        const togglePass = document.getElementById('togglePass');
        const password = document.getElementById('password');

        togglePass?.addEventListener('click', function () {
            const isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            this.innerHTML = isPassword
                ? '<i class="fa-solid fa-eye-slash"></i>'
                : '<i class="fa-solid fa-eye"></i>';
        });
    </script>
</body>
</html>