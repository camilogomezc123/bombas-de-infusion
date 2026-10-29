@extends('portal.layout')

@section('title', 'Cambia tu contraseña')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <h1 class="h4 mb-1">Cambia tu contraseña</h1>
                <p class="text-muted mb-4">Por seguridad, debes elegir una contraseña nueva antes de continuar.</p>

                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('portal.password.force-change.submit') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required minlength="8" autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                        <input type="password" class="form-control form-control-lg" id="password_confirmation" name="password_confirmation" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-posuci btn-lg w-100">Guardar y continuar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
