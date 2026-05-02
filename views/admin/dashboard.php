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

<?php if ($hotel): ?>
<h4 class="mb-4"><?= htmlspecialchars($hotel->name ?? 'Hotel', ENT_QUOTES, 'UTF-8') ?> &mdash; Operations Dashboard</h4>
<?php endif; ?>

<!-- Key Metrics -->
<div class="row mb-4">
    <!-- Total Rooms -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-primary text-white py-2">
                <i class="fas fa-bed"></i> Total Rooms
            </div>
            <div class="card-body text-center">
                <h2 class="mb-1"><?= (int)($roomStats['total_rooms'] ?? 0) ?></h2>
                <div class="small">
                    <span class="text-success"><i class="fas fa-check-circle"></i> <?= (int)($roomStats['available'] ?? 0) ?> available</span>
                    <span class="mx-1">|</span>
                    <span class="text-danger"><i class="fas fa-times-circle"></i> <?= (int)($roomStats['unavailable'] ?? 0) ?> unavailable</span>
                </div>
                <?php if ((int)($roomStats['in_maintenance'] ?? 0) > 0): ?>
                <div class="small text-warning mt-1">
                    <i class="fas fa-wrench"></i> <?= (int)$roomStats['in_maintenance'] ?> in maintenance
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Occupancy Rate -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-success text-white py-2">
                <i class="fas fa-chart-pie"></i> Today's Occupancy
            </div>
            <div class="card-body text-center">
                <h2 class="mb-1"><?= htmlspecialchars((string)$occupancyRate, ENT_QUOTES, 'UTF-8') ?>%</h2>
                <div class="progress mb-2" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar"
                         style="width: <?= min((float)$occupancyRate, 100) ?>%"
                         aria-valuenow="<?= (float)$occupancyRate ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="small text-muted">
                    <?= (int)$occupiedRooms ?> of <?= (int)($roomStats['available'] ?? 0) ?> rooms occupied
                </div>
            </div>
        </div>
    </div>

    <!-- Total Bookings -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header text-white py-2" style="background-color: #fd7e14;">
                <i class="fas fa-calendar-check"></i> Total Bookings
            </div>
            <div class="card-body text-center">
                <h2 class="mb-1"><?= (int)($bookingStats['total_bookings'] ?? 0) ?></h2>
                <div class="small">
                    <span class="text-warning"><?= (int)($bookingStats['pending'] ?? 0) ?> pending</span>
                    <span class="mx-1">|</span>
                    <span class="text-success"><?= (int)($bookingStats['confirmed'] ?? 0) ?> confirmed</span>
                </div>
                <div class="small text-muted mt-1">
                    <?= (int)($bookingStats['cancelled'] ?? 0) ?> cancelled
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue -->
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header text-white py-2" style="background-color: #6f42c1;">
                <i class="fas fa-rupee-sign"></i> Total Revenue
            </div>
            <div class="card-body text-center">
                <h2 class="mb-1">&#8377;<?= number_format((float)($bookingStats['total_revenue'] ?? 0)) ?></h2>
                <div class="small text-muted">
                    <i class="fas fa-users"></i> <?= (int)$userCount ?> registered users
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Today's Activity -->
<div class="row mb-4">
    <!-- Today's Check-ins -->
    <div class="col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-info text-white py-2">
                <i class="fas fa-sign-in-alt"></i> Today's Check-ins
                <span class="badge bg-light text-dark float-end"><?= count($todayCheckIns) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($todayCheckIns)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayCheckIns as $ci): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($ci['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($ci['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                </td>
                                <td><?= htmlspecialchars($ci['room_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><small><?= htmlspecialchars(ucwords(str_replace('_', ' ', $ci['room_type'] ?? '')), ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td>
                                    <?php
                                    $ciStatus = $ci['status'] ?? 'pending';
                                    $ciClass = $ciStatus === 'confirmed' ? 'success' : 'warning';
                                    ?>
                                    <span class="badge bg-<?= $ciClass ?>"><?= htmlspecialchars(ucwords($ciStatus), ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="p-3 text-center text-muted">
                    <i class="fas fa-calendar-day"></i> No check-ins scheduled for today.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Today's Check-outs -->
    <div class="col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-secondary text-white py-2">
                <i class="fas fa-sign-out-alt"></i> Today's Check-outs
                <span class="badge bg-light text-dark float-end"><?= count($todayCheckOuts) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($todayCheckOuts)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayCheckOuts as $co): ?>
                            <tr>
                                <td><?= htmlspecialchars($co['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($co['room_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><small><?= htmlspecialchars(ucwords(str_replace('_', ' ', $co['room_type'] ?? '')), ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td><span class="badge bg-info">Checked In</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="p-3 text-center text-muted">
                    <i class="fas fa-calendar-day"></i> No check-outs scheduled for today.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white py-2">
                <i class="fas fa-history"></i> Recent Activity
                <a href="/admin/logs" class="btn btn-sm btn-outline-light float-end">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recentActivity)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Severity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivity as $log): ?>
                            <tr>
                                <td><small><?= htmlspecialchars($log['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td><?= htmlspecialchars($log['username'] ?? 'System', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><code><?= htmlspecialchars($log['action'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><small><?= htmlspecialchars($log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td>
                                    <?php
                                    $sev = $log['severity'] ?? 'info';
                                    $sevClass = match($sev) {
                                        'critical' => 'dark',
                                        'error' => 'danger',
                                        'warning' => 'warning',
                                        default => 'light text-dark',
                                    };
                                    ?>
                                    <span class="badge bg-<?= $sevClass ?>"><?= htmlspecialchars($sev, ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    No activity recorded yet.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
