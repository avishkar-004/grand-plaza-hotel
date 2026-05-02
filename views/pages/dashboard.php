<?php
/** @var \App\Models\User $user_data */
/** @var array $upcoming_bookings */
/** @var array $recent_activity */
/** @var array $stats */
/** @var string $csrf_token */
?>

<!-- Welcome Banner -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-primary text-white shadow">
            <div class="card-body py-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            Welcome back, <?= htmlspecialchars($user_data->getDisplayName(), ENT_QUOTES, 'UTF-8') ?>!
                            <span class="badge bg-light text-primary ms-2 fs-6"><?= htmlspecialchars(ucfirst($user_data->role), ENT_QUOTES, 'UTF-8') ?></span>
                        </h2>
                        <?php if ($user_data->last_login): ?>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-clock"></i>
                                Last login: <?= htmlspecialchars($user_data->last_login, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="d-none d-md-block">
                        <i class="fas fa-tachometer-alt fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3 mb-md-0">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-primary mb-2">
                    <i class="fas fa-calendar-check fa-2x"></i>
                </div>
                <h3 class="mb-0"><?= (int)$stats['total_bookings'] ?></h3>
                <p class="text-muted mb-0">Total Bookings</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3 mb-md-0">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-success mb-2">
                    <i class="fas fa-door-open fa-2x"></i>
                </div>
                <h3 class="mb-0"><?= (int)$stats['active_bookings'] ?></h3>
                <p class="text-muted mb-0">Active Bookings</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center">
                <div style="color: #6f42c1;" class="mb-2">
                    <i class="fas fa-indian-rupee-sign fa-2x"></i>
                </div>
                <h3 class="mb-0">&#8377;<?= htmlspecialchars(number_format((float)$stats['total_spent']), ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="text-muted mb-0">Total Spent</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Upcoming Bookings -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Upcoming Bookings</h5>
                <a href="/bookings" class="btn btn-sm btn-outline-primary">View All Bookings</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($upcoming_bookings)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Room</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($booking['booking_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($booking['room_type'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            <small class="text-muted">#<?= htmlspecialchars($booking['room_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($booking['check_in'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($booking['check_out'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php
                                                $status = $booking['status'] ?? 'unknown';
                                                $badgeClass = match ($status) {
                                                    'confirmed' => 'bg-success',
                                                    'pending' => 'bg-warning text-dark',
                                                    'checked_in' => 'bg-info',
                                                    'checked_out' => 'bg-secondary',
                                                    'cancelled' => 'bg-danger',
                                                    'no_show' => 'bg-dark',
                                                    default => 'bg-secondary',
                                                };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td class="text-end">&#8377;<?= htmlspecialchars(number_format((float)($booking['total_price'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-1">No upcoming bookings.</p>
                        <a href="/rooms" class="btn btn-primary mt-2">Browse Our Rooms</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar: Quick Actions + Activity -->
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="/rooms" class="btn btn-primary">
                    <i class="fas fa-search"></i> Browse Rooms
                </a>
                <a href="/bookings" class="btn btn-outline-primary">
                    <i class="fas fa-calendar-check"></i> My Bookings
                </a>
                <a href="/profile" class="btn btn-outline-secondary">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recent_activity)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recent_activity as $log): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong class="d-block"><?= htmlspecialchars($log['action'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small class="text-muted"><?= htmlspecialchars($log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                    </div>
                                    <small class="text-muted text-nowrap ms-2"><?= htmlspecialchars($log['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">No recent activity.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
