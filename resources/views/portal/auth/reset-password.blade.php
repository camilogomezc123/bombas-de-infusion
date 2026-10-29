@extends('portal.layout')

@section('title', 'Restablecer contraseña')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <h1 class="h4 mb-1">Restablecer contraseña</h1>
                <p class="text-muted mb-4">Elige una nueva contraseña de al menos 8 caracteres.</p>

                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('portal.password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="guard" value="{{ $guard }}">
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" value="{{ old('email', $email) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required minlength="8">
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                        <input type="password" class="form-control form-control-lg" id="password_confirmation" name="password_confirmation" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-posuci btn-lg w-100">Guardar nueva contraseña</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
