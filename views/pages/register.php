<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-body p-5">
                <h2 class="card-title text-center mb-4">
                    <i class="fas fa-user-plus"></i> Create Account
                </h2>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/register">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required minlength="3" pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscores only">
                        <small class="text-muted">3-50 characters, alphanumeric only</small>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters (12+ recommended)</small>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9+\-\s()]+">
                        <small class="text-muted">Optional</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>

                    <div class="text-center">
                        <p class="mb-0">Already have an account? <a href="/login">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
