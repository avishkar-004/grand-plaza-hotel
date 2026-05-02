<!-- Admin Navigation -->
<div class="bg-dark text-white p-3 rounded mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-cog"></i> Admin Panel</h5>
        <nav>
            <a href="/admin" class="text-white text-decoration-none mx-2"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="/admin/rooms" class="text-white text-decoration-none mx-2"><i class="fas fa-bed"></i> Rooms</a>
            <a href="/admin/bookings" class="text-white text-decoration-none mx-2"><i class="fas fa-calendar"></i> Bookings</a>
            <a href="/admin/users" class="text-white text-decoration-none mx-2"><i class="fas fa-users"></i> Users</a>
            <a href="/admin/settings" class="text-white text-decoration-none mx-2"><i class="fas fa-hotel"></i> Settings</a>
            <a href="/admin/logs" class="text-white text-decoration-none mx-2"><i class="fas fa-clipboard-list"></i> Logs</a>
        </nav>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-users"></i> User Management</h4>
    <span class="badge bg-primary fs-6"><?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?></span>
</div>

<!-- Search -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="/admin/users" method="GET" class="row g-3 align-items-end">
            <div class="col-md-9">
                <label for="search" class="form-label">Search Users</label>
                <input type="text" name="search" id="search" class="form-control"
                       placeholder="Search by username, email, or full name..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search"></i> Search</button>
                <a href="/admin/users" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<?php if ($search !== ''): ?>
<div class="alert alert-info">
    Showing results for: <strong><?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?></strong>
</div>
<?php endif; ?>

<!-- Users Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($users)): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th>Failed Attempts</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <?php
                        $lockedUntil = $u['locked_until'] ?? null;
                        $isLocked = $lockedUntil && strtotime($lockedUntil) > time();
                        $failedAttempts = (int)($u['failed_login_attempts'] ?? 0);
                    ?>
                    <tr class="<?= $isLocked ? 'table-danger' : '' ?>">
                        <td><?= (int)($u['id'] ?? 0) ?></td>
                        <td><strong><?= htmlspecialchars($u['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                            $role = $u['role'] ?? 'user';
                            $roleClass = match($role) {
                                'admin' => 'danger',
                                default => 'primary',
                            };
                            ?>
                            <span class="badge bg-<?= $roleClass ?>"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <?php if (!empty($u['last_login'])): ?>
                                <small><?= htmlspecialchars($u['last_login'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php if (!empty($u['last_login_ip'])): ?>
                                <br><small class="text-muted"><i class="fas fa-globe"></i> <?= htmlspecialchars($u['last_login_ip'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <small class="text-muted">Never</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isLocked): ?>
                                <span class="badge bg-danger"><i class="fas fa-lock"></i> Locked</span>
                                <br><small class="text-danger">Until: <?= htmlspecialchars($lockedUntil, ENT_QUOTES, 'UTF-8') ?></small>
                            <?php elseif (!empty($u['is_active'])): ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($failedAttempts > 0): ?>
                                <span class="badge bg-<?= $failedAttempts >= 5 ? 'danger' : 'warning' ?>">
                                    <?= $failedAttempts ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $currentUserId = $_SESSION['user_id'] ?? 0; ?>
                            <?php if ((int)($u['id'] ?? 0) !== (int)$currentUserId): ?>
                                <form method="POST" action="/admin/users/toggle/<?= (int)$u['id'] ?>" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-sm btn-<?= !empty($u['is_active']) ? 'warning' : 'success' ?>" onclick="return confirm('<?= !empty($u['is_active']) ? 'Deactivate' : 'Activate' ?> this user?')">
                                        <?= !empty($u['is_active']) ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <form method="POST" action="/admin/users/role/<?= (int)$u['id'] ?>" class="d-inline ms-1">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                    <select name="new_role" class="form-select form-select-sm d-inline w-auto" onchange="if(confirm('Change role to ' + this.value + '?')) this.form.submit();">
                                        <option value="user" <?= ($u['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>User</option>
                                        <option value="admin" <?= ($u['role'] ?? 'user') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">Current user</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-users fa-3x mb-3 d-block"></i>
            No users found.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($pagination)) include __DIR__ . '/../components/pagination.php'; ?>
