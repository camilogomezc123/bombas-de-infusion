@extends('portal.layout')

@section('title', 'Olvidé mi contraseña')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <h1 class="h4 mb-1">¿Olvidaste tu contraseña?</h1>
                <p class="text-muted mb-4">Escribe tu correo y te enviaremos instrucciones para restablecerla.</p>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('portal.password.email') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-posuci btn-lg w-100">Enviar instrucciones</button>
                </form>
                <p class="text-center mt-3 mb-0">
                    <a href="{{ route('portal.login') }}">Volver a ingresar</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
