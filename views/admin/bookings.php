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
    <h4><i class="fas fa-calendar-check"></i> Booking Management</h4>
    <span class="badge bg-primary fs-6"><?= count($bookings) ?> booking<?= count($bookings) !== 1 ? 's' : '' ?></span>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="/admin/bookings" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach ($allowedStatuses as $s): ?>
                    <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>"
                            <?= ($statusFilter === $s) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $s)), ENT_QUOTES, 'UTF-8') ?>
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
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" name="search" id="search" class="form-control"
                       placeholder="Booking ref or guest name..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary me-1"><i class="fas fa-filter"></i> Filter</button>
                <a href="/admin/bookings" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Bookings Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($bookings)): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Ref</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Nights</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <?php
                        $checkIn = $b['check_in'] ?? '';
                        $checkOut = $b['check_out'] ?? '';
                        $nights = 0;
                        if ($checkIn && $checkOut) {
                            $diff = strtotime($checkOut) - strtotime($checkIn);
                            if ($diff > 0) {
                                $nights = (int)($diff / 86400);
                            }
                        }
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($b['booking_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td>
                            <?= htmlspecialchars($b['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            <br><small class="text-muted"><?= htmlspecialchars($b['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($b['room_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            <br><small class="text-muted"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $b['room_type'] ?? '')), ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td><?= htmlspecialchars($checkIn, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($checkOut, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $nights ?></td>
                        <td><strong>&#8377;<?= number_format((float)($b['total_price'] ?? 0)) ?></strong></td>
                        <td>
                            <?php
                            $bStatus = $b['status'] ?? 'unknown';
                            $statusClass = match($bStatus) {
                                'confirmed' => 'success',
                                'pending' => 'warning',
                                'checked_in' => 'info',
                                'checked_out' => 'secondary',
                                'cancelled' => 'danger',
                                'no_show' => 'dark',
                                default => 'light text-dark',
                            };
                            ?>
                            <span class="badge bg-<?= $statusClass ?>">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $bStatus)), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $payment = $b['payment_status'] ?? 'unknown';
                            $payClass = match($payment) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                'refunded' => 'info',
                                'failed' => 'danger',
                                'partial' => 'primary',
                                default => 'secondary',
                            };
                            ?>
                            <span class="badge bg-<?= $payClass ?>">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $payment)), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <a href="/admin/booking/<?= (int)($b['id'] ?? 0) ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($bStatus === 'confirmed'): ?>
                            <form method="POST" action="/admin/bookings/status/<?= (int)($b['id'] ?? 0) ?>" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="new_status" value="checked_in">
                                <button type="submit" class="btn btn-sm btn-success" title="Check In" onclick="return confirm('Check in this guest?')">
                                    <i class="fas fa-sign-in-alt"></i> Check In
                                </button>
                            </form>
                            <?php elseif ($bStatus === 'checked_in'): ?>
                            <form method="POST" action="/admin/bookings/status/<?= (int)($b['id'] ?? 0) ?>" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="new_status" value="checked_out">
                                <button type="submit" class="btn btn-sm btn-info" title="Check Out" onclick="return confirm('Check out this guest?')">
                                    <i class="fas fa-sign-out-alt"></i> Check Out
                                </button>
                            </form>
                            <?php elseif ($bStatus === 'pending'): ?>
                            <form method="POST" action="/admin/bookings/status/<?= (int)($b['id'] ?? 0) ?>" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="new_status" value="confirmed">
                                <button type="submit" class="btn btn-sm btn-success" title="Confirm">
                                    <i class="fas fa-check"></i> Confirm
                                </button>
                            </form>
                            <form method="POST" action="/admin/bookings/status/<?= (int)($b['id'] ?? 0) ?>" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="new_status" value="cancelled">
                                <button type="submit" class="btn btn-sm btn-danger" title="Cancel" onclick="return confirm('Cancel this booking?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
            No bookings match the current filters.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($pagination)) include __DIR__ . '/../components/pagination.php'; ?>
