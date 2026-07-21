<x-guest-layout>
    <!-- Session Status -->
    @if(session('status'))
        <div class="alert alert-success clay-card-sm mb-4">{{ session('status') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger clay-card-sm mb-4">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email -->
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" class="clay-input @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Enter your email">
            @error('email')
                <div class="error-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" class="clay-input @error('password') is-invalid @enderror"
                   required autocomplete="current-password" placeholder="Enter your password">
            @error('password')
                <div class="error-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="mb-3 form-check">
            <input id="remember_me" type="checkbox" name="remember" class="form-check-input">
            <label for="remember_me" class="form-check-label" style="color:var(--clay-text);">Remember me</label>
        </div>

        <button type="submit" class="clay-btn clay-btn-primary mb-3">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>

        @if (Route::has('password.request'))
            <div class="text-center">
                <a href="{{ route('password.request') }}" style="color:var(--clay-primary);text-decoration:none;font-size:0.9rem;">
                    Forgot your password?
                </a>
            </div>
        @endif
    </form>

    <div class="text-center mt-4">
        <small class="text-muted">Default: admin@brms.local / password (Admin) or worker1@brms.local / password (Worker)</small>
    </div>
</x-guest-layout>
