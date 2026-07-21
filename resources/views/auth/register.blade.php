<x-guest-layout>
    <!-- Status Messages -->
    @if(session('status'))
        <div class="alert alert-success clay-card-sm mb-4">{{ session('status') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger clay-card-sm mb-4">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('register') }}" id="register-form">
        @csrf

        <!-- Role Selection -->
        <div class="mb-4">
            <label class="form-label">I want to register as</label>
            <div class="role-toggle" id="role-toggle">
                <input type="hidden" name="role" id="role-input" value="worker">
                <button type="button" class="role-option active" data-role="worker" onclick="selectRole('worker')">
                    <i class="bi bi-person-badge"></i>
                    <span>Worker</span>
                </button>
                <button type="button" class="role-option" data-role="admin" onclick="selectRole('admin')">
                    <i class="bi bi-shield-check"></i>
                    <span>Admin</span>
                </button>
            </div>
        </div>

        <!-- Name -->
        <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <div class="input-icon-wrapper">
                <i class="bi bi-person input-icon"></i>
                <input id="name" type="text" name="name"
                       class="clay-input @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" required autofocus autocomplete="name"
                       placeholder="Enter your full name">
            </div>
            @error('name')
                <div class="error-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-icon-wrapper">
                <i class="bi bi-envelope input-icon"></i>
                <input id="email" type="email" name="email"
                       class="clay-input @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" required autocomplete="username"
                       placeholder="Enter your email address">
            </div>
            @error('email')
                <div class="error-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-icon-wrapper">
                <i class="bi bi-lock input-icon"></i>
                <input id="password" type="password" name="password"
                       class="clay-input @error('password') is-invalid @enderror"
                       required autocomplete="new-password"
                       placeholder="Create a strong password"
                       oninput="checkPasswordStrength(this.value)">
                <button type="button" class="password-toggle" onclick="togglePassword('password', this)" tabindex="-1">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            @error('password')
                <div class="error-feedback">{{ $message }}</div>
            @enderror
            <!-- Password Strength -->
            <div class="password-strength mt-2" id="password-strength" style="display:none;">
                <div class="strength-bar">
                    <div class="strength-fill" id="strength-fill"></div>
                </div>
                <small class="strength-text" id="strength-text"></small>
            </div>
            <div class="password-requirements mt-2">
                <small class="text-muted">Minimum 8 characters with at least one letter and one number</small>
            </div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <div class="input-icon-wrapper">
                <i class="bi bi-lock-fill input-icon"></i>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       class="clay-input @error('password_confirmation') is-invalid @enderror"
                       required autocomplete="new-password"
                       placeholder="Confirm your password"
                       oninput="checkPasswordMatch(this.value)">
                <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', this)" tabindex="-1">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <div class="match-feedback mt-1" id="match-feedback"></div>
            @error('password_confirmation')
                <div class="error-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Terms Checkbox -->
        <div class="mb-4 form-check">
            <input id="terms" type="checkbox" name="terms" class="form-check-input" required>
            <label for="terms" class="form-check-label" style="color:var(--clay-text);font-size:0.85rem;">
                I agree to the <a href="#" style="color:var(--clay-primary);text-decoration:none;font-weight:600;">Terms of Service</a> and <a href="#" style="color:var(--clay-primary);text-decoration:none;font-weight:600;">Privacy Policy</a>
            </label>
        </div>

        <button type="submit" class="clay-btn clay-btn-primary mb-3" id="register-btn">
            <i class="bi bi-person-plus me-2"></i>Create Account
        </button>

        <div class="text-center">
            <small class="text-muted">
                Already have an account?
                <a href="{{ route('login') }}" style="color:var(--clay-primary);text-decoration:none;font-weight:600;">
                    Sign in here
                </a>
            </small>
        </div>
    </form>

    @push('scripts')
    <script>
        /**
         * Toggle role selection (worker/admin)
         */
        function selectRole(role) {
            document.getElementById('role-input').value = role;
            document.querySelectorAll('.role-option').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.role === role);
            });
        }

        /**
         * Toggle password visibility
         */
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.querySelector('i').className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        }

        /**
         * Check password strength and update UI
         */
        function checkPasswordStrength(password) {
            const container = document.getElementById('password-strength');
            const fill = document.getElementById('strength-fill');
            const text = document.getElementById('strength-text');
            if (!container || !fill || !text) return;

            if (!password) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';

            let score = 0;
            if (password.length >= 8) score += 25;
            if (password.length >= 12) score += 15;
            if (/[a-z]/.test(password)) score += 15;
            if (/[A-Z]/.test(password)) score += 15;
            if (/[0-9]/.test(password)) score += 15;
            if (/[^a-zA-Z0-9]/.test(password)) score += 15;

            fill.style.width = Math.min(100, score) + '%';

            if (score < 30) {
                fill.style.background = '#dc3545';
                text.textContent = 'Weak';
                text.style.color = '#dc3545';
            } else if (score < 60) {
                fill.style.background = '#ffc107';
                text.textContent = 'Fair';
                text.style.color = '#ffc107';
            } else if (score < 80) {
                fill.style.background = '#0d6efd';
                text.textContent = 'Good';
                text.style.color = '#0d6efd';
            } else {
                fill.style.background = '#198754';
                text.textContent = 'Strong';
                text.style.color = '#198754';
            }
        }

        /**
         * Check if passwords match and show feedback
         */
        function checkPasswordMatch(value) {
            const password = document.getElementById('password')?.value || '';
            const feedback = document.getElementById('match-feedback');
            if (!feedback) return;

            if (!value) {
                feedback.innerHTML = '';
                return;
            }

            if (value === password) {
                feedback.innerHTML = '<small style="color:#198754;"><i class="bi bi-check-circle-fill"></i> Passwords match</small>';
            } else {
                feedback.innerHTML = '<small style="color:#dc3545;"><i class="bi bi-exclamation-circle-fill"></i> Passwords do not match</small>';
            }
        }
    </script>

    <style>
        /* ── Role Toggle ── */
        .role-toggle {
            display: flex;
            gap: 10px;
        }
        .role-option {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 16px;
            border: 2px solid transparent;
            border-radius: var(--clay-radius-sm);
            background: var(--clay-bg);
            box-shadow: var(--clay-inset-sm);
            color: var(--clay-text-light);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition-base);
        }
        .role-option i {
            font-size: 1.2rem;
        }
        .role-option:hover {
            color: var(--clay-text);
            box-shadow: var(--clay-shadow-sm);
        }
        .role-option.active {
            background: var(--clay-card);
            box-shadow: var(--clay-shadow-sm);
            color: var(--clay-primary);
            border-color: var(--clay-primary);
        }

        /* ── Input Icon Wrapper ── */
        .input-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-icon-wrapper .clay-input {
            padding-left: 44px;
            padding-right: 44px;
        }
        .input-icon-wrapper .input-icon {
            position: absolute;
            left: 14px;
            color: var(--clay-text-light);
            font-size: 1.1rem;
            z-index: 2;
            pointer-events: none;
            transition: color 0.3s ease;
        }
        .input-icon-wrapper .clay-input:focus ~ .input-icon {
            color: var(--clay-primary);
        }

        /* ── Password Toggle ── */
        .password-toggle {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            color: var(--clay-text-light);
            font-size: 1.1rem;
            padding: 6px;
            cursor: pointer;
            z-index: 2;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .password-toggle:hover {
            color: var(--clay-primary);
            background: rgba(108, 92, 231, 0.08);
        }

        /* ── Password Strength ── */
        .strength-bar {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .strength-text {
            font-weight: 600;
            font-size: 0.75rem;
            margin-top: 2px;
            display: block;
        }

        /* ── Match Feedback ── */
        .match-feedback small {
            font-size: 0.8rem;
        }

        /* ── Password Requirements ── */
        .password-requirements small {
            font-size: 0.75rem;
            color: var(--clay-text-light);
        }

        /* ── Terms Checkbox ── */
        .form-check-input {
            box-shadow: var(--clay-inset-sm);
            border: none;
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: var(--clay-primary);
            border-color: var(--clay-primary);
            box-shadow: var(--clay-shadow-sm);
        }

        /* ── Alert Styling ── */
        .clay-card-sm {
            background: var(--clay-card);
            border-radius: var(--clay-radius-sm);
            box-shadow: var(--clay-shadow-sm);
            border: none;
            padding: 12px 16px;
        }
        .alert-success.clay-card-sm {
            border-left: 4px solid var(--clay-success);
        }
        .alert-danger.clay-card-sm {
            border-left: 4px solid var(--clay-danger);
        }

        /* ── Animate form entrance ── */
        .role-toggle, .mb-3, .mb-4 {
            animation: fadeUp 0.5s ease both;
        }
        .role-toggle { animation-delay: 0.05s; }
        .mb-3:nth-of-type(1) { animation-delay: 0.1s; }
        .mb-3:nth-of-type(2) { animation-delay: 0.15s; }
        .mb-3:nth-of-type(3) { animation-delay: 0.2s; }
        .mb-4:nth-of-type(3) { animation-delay: 0.25s; }
        .mb-4:nth-of-type(4) { animation-delay: 0.3s; }
        .clay-btn-primary { animation: fadeUp 0.5s ease 0.35s both; }
        .text-center { animation: fadeUp 0.5s ease 0.4s both; }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    @endpush
</x-guest-layout>
