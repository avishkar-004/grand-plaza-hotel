<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-body p-5">
                <h2 class="card-title text-center mb-3">
                    <i class="fas fa-key"></i> Forgot Password
                </h2>
                <p class="text-muted text-center mb-4">Enter your email address and we'll send you a reset link.</p>

                <?php if (isset($error) && $error): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($reset_link) && $reset_link): ?>
                    <div class="alert alert-info">
                        <strong>Demo Mode:</strong> Password reset link (in production this would be emailed):<br>
                        <a href="<?= htmlspecialchars($reset_link, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($reset_link, ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/forgot-password">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required autofocus
                                   placeholder="Enter your registered email">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </button>

                    <div class="text-center">
                        <a href="/login" class="text-muted"><i class="fas fa-arrow-left"></i> Back to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
