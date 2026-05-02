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
    <h4><i class="fas fa-clipboard-list"></i> Activity Log</h4>
    <span class="badge bg-primary fs-6"><?= count($logs) ?> entr<?= count($logs) !== 1 ? 'ies' : 'y' ?></span>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="/admin/logs" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="severity" class="form-label">Severity</label>
                <select name="severity" id="severity" class="form-select">
                    <option value="">All Severities</option>
                    <?php foreach ($allowedSeverities as $s): ?>
                    <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>"
                            <?= ($severity === $s) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">From</label>
                <input type="date" name="date_from" id="date_from" class="form-control"
                       value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">To</label>
                <input type="date" name="date_to" id="date_to" class="form-control"
                       value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-5">
                <button type="submit" class="btn btn-primary me-1"><i class="fas fa-filter"></i> Filter</button>
                <a href="/admin/logs" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($logs)): ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Severity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <?php
                        $logSev = $log['severity'] ?? 'info';
                        $sevClass = match($logSev) {
                            'critical' => 'dark',
                            'error' => 'danger',
                            'warning' => 'warning',
                            default => 'light text-dark',
                        };
                    ?>
                    <tr>
                        <td><small><?= htmlspecialchars($log['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                        <td><?= htmlspecialchars($log['username'] ?? 'System', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><code><?= htmlspecialchars($log['action'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><small><?= htmlspecialchars($log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                        <td><code><?= htmlspecialchars($log['ip_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><span class="badge bg-<?= $sevClass ?>"><?= htmlspecialchars($logSev, ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-clipboard-list fa-3x mb-3 d-block"></i>
            No log entries match the current filters.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($pagination)) include __DIR__ . '/../components/pagination.php'; ?>
