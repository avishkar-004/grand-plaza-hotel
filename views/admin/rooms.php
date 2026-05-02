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
    <h4><i class="fas fa-bed"></i> Room Management</h4>
    <div>
        <span class="badge bg-primary fs-6 me-2"><?= count($rooms) ?> room<?= count($rooms) !== 1 ? 's' : '' ?></span>
        <a href="/admin/rooms/add" class="btn btn-success"><i class="fas fa-plus"></i> Add New Room</a>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="/admin/rooms" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="type" class="form-label">Room Type</label>
                <select name="type" id="type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($roomTypes as $rt): ?>
                    <option value="<?= htmlspecialchars($rt, ENT_QUOTES, 'UTF-8') ?>"
                            <?= ($typeFilter === $rt) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $rt)), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="available" <?= $statusFilter === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="unavailable" <?= $statusFilter === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                    <option value="maintenance" <?= $statusFilter === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter"></i> Filter</button>
                <a href="/admin/rooms" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Rooms Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($rooms)): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Room #</th>
                        <th>Type</th>
                        <th>Floor</th>
                        <th>Price/Night</th>
                        <th>Capacity</th>
                        <th>Beds</th>
                        <th>Status</th>
                        <th>Maintenance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $r): ?>
                    <?php
                        $isAvail = !empty($r['is_available']) && ($r['maintenance_status'] ?? '') === 'operational';
                        $maint = $r['maintenance_status'] ?? 'unknown';
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['room_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $r['room_type'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)($r['floor_number'] ?? 0) ?></td>
                        <td>
                            <strong>&#8377;<?= number_format((float)($r['base_price'] ?? 0)) ?></strong>
                            <?php if (!empty($r['weekend_price'])): ?>
                            <br><small class="text-muted">Wknd: &#8377;<?= number_format((float)$r['weekend_price']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><i class="fas fa-users"></i> <?= (int)($r['max_occupancy'] ?? 0) ?></td>
                        <td>
                            <?= (int)($r['num_beds'] ?? 0) ?>
                            <?php if (!empty($r['bed_type'])): ?>
                            <small class="text-muted">(<?= htmlspecialchars($r['bed_type'], ENT_QUOTES, 'UTF-8') ?>)</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isAvail): ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Available</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Unavailable</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $maintClass = match($maint) {
                                'operational' => 'success',
                                'maintenance' => 'warning',
                                'out_of_order' => 'danger',
                                'cleaning' => 'info',
                                default => 'secondary',
                            };
                            ?>
                            <span class="badge bg-<?= $maintClass ?>">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $maint)), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <a href="/admin/rooms/edit/<?= (int)$r['id'] ?>" class="btn btn-sm btn-primary me-1">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form method="POST" action="/admin/rooms/toggle/<?= (int)$r['id'] ?>" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                <button class="btn btn-sm btn-<?= $r['is_available'] ? 'warning' : 'success' ?>">
                                    <?= $r['is_available'] ? 'Disable' : 'Enable' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-bed fa-3x mb-3 d-block"></i>
            No rooms match the current filters.
        </div>
        <?php endif; ?>
    </div>
</div>
