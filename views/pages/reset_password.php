<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-body p-5">
                <h2 class="card-title text-center mb-3">
                    <i class="fas fa-lock"></i> Reset Password
                </h2>
                <p class="text-muted text-center mb-4">Enter your new password below.</p>

                <?php if (isset($error) && $error): ?>
                    <div class="alert alert-danger">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/reset-password" id="resetPasswordForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password"
                                   required minlength="8" autofocus>
                        </div>
                        <div id="passwordStrength" class="mt-1"></div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   required minlength="8">
                        </div>
                        <div id="passwordMatch" class="mt-1"></div>
                    </div>

                    <div class="mb-3">
                        <p class="small text-muted mb-1"><strong>Password requirements:</strong></p>
                        <ul class="list-unstyled small" id="passwordRequirements">
                            <li id="req-length"><i class="fas fa-times text-danger"></i> At least 8 characters</li>
                            <li id="req-upper"><i class="fas fa-times text-danger"></i> At least one uppercase letter</li>
                            <li id="req-lower"><i class="fas fa-times text-danger"></i> At least one lowercase letter</li>
                            <li id="req-number"><i class="fas fa-times text-danger"></i> At least one number</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn">
                        <i class="fas fa-save"></i> Reset Password
                    </button>

                    <div class="text-center">
                        <a href="/login" class="text-muted"><i class="fas fa-arrow-left"></i> Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var passwordInput = document.getElementById('new_password');
    var confirmInput = document.getElementById('confirm_password');
    var strengthDiv = document.getElementById('passwordStrength');
    var matchDiv = document.getElementById('passwordMatch');
    var submitBtn = document.getElementById('submitBtn');

    function updateRequirement(id, met) {
        var el = document.getElementById(id);
        if (met) {
            el.innerHTML = '<i class="fas fa-check text-success"></i> ' + el.textContent.trim();
        } else {
            el.innerHTML = '<i class="fas fa-times text-danger"></i> ' + el.textContent.trim();
        }
    }

    function checkPassword() {
        var pw = passwordInput.value;
        var hasLength = pw.length >= 8;
        var hasUpper = /[A-Z]/.test(pw);
        var hasLower = /[a-z]/.test(pw);
        var hasNumber = /[0-9]/.test(pw);

        updateRequirement('req-length', hasLength);
        updateRequirement('req-upper', hasUpper);
        updateRequirement('req-lower', hasLower);
        updateRequirement('req-number', hasNumber);

        // Strength indicator
        var score = 0;
        if (hasLength) score++;
        if (hasUpper) score++;
        if (hasLower) score++;
        if (hasNumber) score++;
        if (pw.length >= 12) score++;

        if (pw.length === 0) {
            strengthDiv.innerHTML = '';
        } else if (score <= 2) {
            strengthDiv.innerHTML = '<div class="progress" style="height: 5px;"><div class="progress-bar bg-danger" style="width: 33%;"></div></div><small class="text-danger">Weak</small>';
        } else if (score <= 3) {
            strengthDiv.innerHTML = '<div class="progress" style="height: 5px;"><div class="progress-bar bg-warning" style="width: 66%;"></div></div><small class="text-warning">Fair</small>';
        } else {
            strengthDiv.innerHTML = '<div class="progress" style="height: 5px;"><div class="progress-bar bg-success" style="width: 100%;"></div></div><small class="text-success">Strong</small>';
        }

        checkMatch();
    }

    function checkMatch() {
        var pw = passwordInput.value;
        var cpw = confirmInput.value;

        if (cpw.length === 0) {
            matchDiv.innerHTML = '';
            return;
        }

        if (pw === cpw) {
            matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check"></i> Passwords match</small>';
        } else {
            matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times"></i> Passwords do not match</small>';
        }
    }

    passwordInput.addEventListener('input', checkPassword);
    confirmInput.addEventListener('input', checkMatch);

    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        var pw = passwordInput.value;
        var cpw = confirmInput.value;

        if (pw.length < 8 || !/[A-Z]/.test(pw) || !/[a-z]/.test(pw) || !/[0-9]/.test(pw)) {
            e.preventDefault();
            alert('Please meet all password requirements.');
            return false;
        }

        if (pw !== cpw) {
            e.preventDefault();
            alert('Passwords do not match.');
            return false;
        }
    });
});
</script>
