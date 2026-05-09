<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{
            min-height:100vh;
            margin:0;
            display:grid;
            place-items:center;
            background:linear-gradient(135deg,#07598C,#03658C,#568FA6);
            font-family:Inter,system-ui,sans-serif;
        }
        .login-card{
            width:min(100%,420px);
            background:rgba(255,255,255,.95);
            border-radius:24px;
            padding:28px;
            box-shadow:0 20px 50px rgba(0,0,0,.18);
        }
        .form-control{
            min-height:44px;
            border-radius:12px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h3 class="mb-1 fw-bold">Bienvenido</h3>
        <p class="text-muted mb-4">Inicia sesión para continuar</p>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Alias</label>
                <input type="text" name="alias" class="form-control" value="{{ old('alias') }}" required>
                @error('alias')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
                @error('password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <button class="btn btn-primary w-100">Entrar</button>
        </form>
    </div>
</body>
</html>