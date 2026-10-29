@extends('portal.layout')

@section('title', 'Ingresar')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <h1 class="h4 mb-1">Bienvenido a POSUCI 360 Conecta</h1>
                <p class="text-muted mb-4">Ingresa con el usuario o correo y la contraseña que te dio tu equipo de recuperación.</p>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('portal.login.submit') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="login" class="form-label">Usuario o correo electrónico</label>
                        <input type="text" class="form-control form-control-lg" id="login" name="login" value="{{ old('login') }}" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-posuci btn-lg w-100">Ingresar</button>
                </form>
                <p class="text-center mt-3 mb-0">
                    <a href="{{ route('portal.password.forgot') }}">¿Olvidaste tu contraseña?</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
