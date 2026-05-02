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

<h4 class="mb-4"><i class="fas fa-hotel"></i> Hotel Settings</h4>

<?php if ($hotel): ?>
<!-- Current Info Summary -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary text-white py-2">
        <i class="fas fa-info-circle"></i> Current Hotel Information
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Name:</strong> <?= htmlspecialchars($hotel->name ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($hotel->address ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>City:</strong> <?= htmlspecialchars($hotel->city ?? '', ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($hotel->state ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Phone:</strong> <?= htmlspecialchars($hotel->phone ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($hotel->email ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Check-in:</strong> <?= htmlspecialchars($hotel->check_in_time ?? '', ENT_QUOTES, 'UTF-8') ?> | <strong>Check-out:</strong> <?= htmlspecialchars($hotel->check_out_time ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Edit Form -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-dark text-white py-2">
        <i class="fas fa-edit"></i> Edit Hotel Settings
    </div>
    <div class="card-body">
        <form action="/admin/settings" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="name" class="form-label">Hotel Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required
                           value="<?= htmlspecialchars($hotel->name ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3"><?= htmlspecialchars($hotel->description ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" name="address" id="address" class="form-control"
                           value="<?= htmlspecialchars($hotel->address ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="city" class="form-label">City</label>
                    <input type="text" name="city" id="city" class="form-control"
                           value="<?= htmlspecialchars($hotel->city ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6">
                    <label for="state" class="form-label">State</label>
                    <input type="text" name="state" id="state" class="form-control"
                           value="<?= htmlspecialchars($hotel->state ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control"
                           value="<?= htmlspecialchars($hotel->phone ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control"
                           value="<?= htmlspecialchars($hotel->email ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="check_in_time" class="form-label">Check-in Time</label>
                    <input type="time" name="check_in_time" id="check_in_time" class="form-control"
                           value="<?= htmlspecialchars($hotel->check_in_time ?? '14:00', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6">
                    <label for="check_out_time" class="form-label">Check-out Time</label>
                    <input type="time" name="check_out_time" id="check_out_time" class="form-control"
                           value="<?= htmlspecialchars($hotel->check_out_time ?? '11:00', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <hr>
            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i> Hotel record not found. Please ensure the database is properly seeded.
</div>
<?php endif; ?>
