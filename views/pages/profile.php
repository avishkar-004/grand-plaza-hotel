<?php
/** @var \App\Models\User $user_data */
/** @var string $csrf_token */
/** @var string|null $success */
/** @var string|null $error */
?>

<!-- Flash Messages -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Account Info Card (read-only) -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <h4 class="mb-1"><?= htmlspecialchars($user_data->getDisplayName(), ENT_QUOTES, 'UTF-8') ?></h4>
                <p class="text-muted mb-2">@<?= htmlspecialchars($user_data->username, ENT_QUOTES, 'UTF-8') ?></p>
                <span class="badge bg-primary"><?= htmlspecialchars(ucfirst($user_data->role), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Member since</span>
                    <span><?= htmlspecialchars($user_data->created_at ? date('M j, Y', strtotime($user_data->created_at)) : 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                </li>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Last login</span>
                        <span><?= htmlspecialchars($user_data->last_login ?? 'Never', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-muted">Email verified</span>
                    <span>
                        <?php if ($user_data->email_verified_at): ?>
                            <i class="fas fa-check-circle text-success"></i> Yes
                        <?php else: ?>
                            <i class="fas fa-times-circle text-danger"></i> No
                        <?php endif; ?>
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="col-lg-8">
        <form method="POST" action="/profile" id="profileForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

            <!-- Personal Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-edit"></i> Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name"
                               value="<?= htmlspecialchars($user_data->full_name ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required minlength="2" maxlength="100">
                        <div class="form-text">2-100 characters.</div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= htmlspecialchars($user_data->email ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="<?= htmlspecialchars($user_data->phone ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               pattern="[0-9+\-\s()]{7,20}">
                        <div class="form-text">Optional. 7-20 characters: digits, +, -, spaces, and parentheses only.</div>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-lock"></i> Change Password
                        <button type="button" class="btn btn-sm btn-outline-secondary float-end" data-bs-toggle="collapse" data-bs-target="#passwordSection" aria-expanded="false" aria-controls="passwordSection">
                            <i class="fas fa-chevron-down"></i> Toggle
                        </button>
                    </h5>
                </div>
                <div id="passwordSection" class="collapse">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="current_password" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Required to change your password.</div>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" minlength="8">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="new_password" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="confirm_password" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatchError" class="invalid-feedback">Passwords do not match.</div>
                        </div>

                        <!-- Password Requirements -->
                        <div class="alert alert-light border">
                            <strong>Password Requirements:</strong>
                            <ul class="small text-muted mb-0 mt-1" id="passwordRequirements">
                                <li id="req-length">At least 8 characters</li>
                                <li id="req-upper">At least one uppercase letter</li>
                                <li id="req-lower">At least one lowercase letter</li>
                                <li id="req-number">At least one number</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-flex justify-content-between">
                <a href="/dashboard" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Profile
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var newPassword = document.getElementById('new_password');
    var confirmPassword = document.getElementById('confirm_password');

    // Password requirements live check
    var reqLength = document.getElementById('req-length');
    var reqUpper = document.getElementById('req-upper');
    var reqLower = document.getElementById('req-lower');
    var reqNumber = document.getElementById('req-number');

    function checkRequirements() {
        var val = newPassword.value;
        reqLength.className = val.length >= 8 ? 'text-success' : 'text-muted';
        reqUpper.className = /[A-Z]/.test(val) ? 'text-success' : 'text-muted';
        reqLower.className = /[a-z]/.test(val) ? 'text-success' : 'text-muted';
        reqNumber.className = /[0-9]/.test(val) ? 'text-success' : 'text-muted';
    }

    function checkMatch() {
        if (confirmPassword.value === '' && newPassword.value === '') {
            confirmPassword.classList.remove('is-invalid', 'is-valid');
            return;
        }
        if (confirmPassword.value !== newPassword.value) {
            confirmPassword.classList.add('is-invalid');
            confirmPassword.classList.remove('is-valid');
        } else {
            confirmPassword.classList.remove('is-invalid');
            confirmPassword.classList.add('is-valid');
        }
    }

    newPassword.addEventListener('input', function () {
        checkRequirements();
        if (confirmPassword.value !== '') {
            checkMatch();
        }
    });

    confirmPassword.addEventListener('input', checkMatch);

    // Form submission: block if passwords don't match
    document.getElementById('profileForm').addEventListener('submit', function (e) {
        if (newPassword.value !== '' && newPassword.value !== confirmPassword.value) {
            e.preventDefault();
            confirmPassword.classList.add('is-invalid');
            confirmPassword.focus();
        }
    });

    // Show/hide password toggle
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            var icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
});
</script>
