<?php
$isEdit = isset($room);
$title = $isEdit ? 'Edit Room #' . htmlspecialchars($room->room_number ?? '', ENT_QUOTES, 'UTF-8') : 'Add New Room';
?>

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
    <h4><i class="fas fa-bed"></i> <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h4>
    <a href="/admin/rooms" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Rooms</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-dark text-white py-2">
        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle' ?>"></i> <?= $isEdit ? 'Edit Room Details' : 'New Room Details' ?>
    </div>
    <div class="card-body">
        <form action="<?= $isEdit ? '/admin/rooms/edit/' . (int)$room->id : '/admin/rooms/add' ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="room_number" class="form-label">Room Number <span class="text-danger">*</span></label>
                    <input type="text" name="room_number" id="room_number" class="form-control" required
                           value="<?= htmlspecialchars($isEdit ? ($room->room_number ?? '') : '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="e.g. 101">
                </div>
                <div class="col-md-4">
                    <label for="floor_number" class="form-label">Floor Number <span class="text-danger">*</span></label>
                    <input type="number" name="floor_number" id="floor_number" class="form-control" required
                           min="1" max="50"
                           value="<?= htmlspecialchars($isEdit ? ($room->floor_number ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label for="room_type" class="form-label">Room Type <span class="text-danger">*</span></label>
                    <select name="room_type" id="room_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <?php foreach ($room_types as $rt): ?>
                        <option value="<?= htmlspecialchars($rt, ENT_QUOTES, 'UTF-8') ?>"
                                <?= ($isEdit && ($room->room_type ?? '') === $rt) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $rt)), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3"
                              placeholder="Brief description of the room"><?= htmlspecialchars($isEdit ? ($room->description ?? '') : '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <hr>
            <h6 class="text-muted mb-3"><i class="fas fa-rupee-sign"></i> Pricing</h6>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="base_price" class="form-label">Base Price &#8377; <span class="text-danger">*</span></label>
                    <input type="number" name="base_price" id="base_price" class="form-control" required
                           min="1" step="100"
                           value="<?= htmlspecialchars($isEdit ? ($room->base_price ?? '') : '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="e.g. 5000">
                </div>
                <div class="col-md-4">
                    <label for="weekend_price" class="form-label">Weekend Price &#8377;</label>
                    <input type="number" name="weekend_price" id="weekend_price" class="form-control"
                           min="0" step="100"
                           value="<?= htmlspecialchars($isEdit ? ($room->weekend_price ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label for="peak_season_price" class="form-label">Peak Season Price &#8377;</label>
                    <input type="number" name="peak_season_price" id="peak_season_price" class="form-control"
                           min="0" step="100"
                           value="<?= htmlspecialchars($isEdit ? ($room->peak_season_price ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <hr>
            <h6 class="text-muted mb-3"><i class="fas fa-bed"></i> Capacity &amp; Beds</h6>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="max_occupancy" class="form-label">Max Occupancy <span class="text-danger">*</span></label>
                    <input type="number" name="max_occupancy" id="max_occupancy" class="form-control" required
                           min="1" max="20"
                           value="<?= htmlspecialchars($isEdit ? ($room->max_occupancy ?? '2') : '2', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label for="num_beds" class="form-label">Number of Beds <span class="text-danger">*</span></label>
                    <input type="number" name="num_beds" id="num_beds" class="form-control" required
                           min="1" max="10"
                           value="<?= htmlspecialchars($isEdit ? ($room->num_beds ?? '1') : '1', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label for="bed_type" class="form-label">Bed Type</label>
                    <select name="bed_type" id="bed_type" class="form-select">
                        <option value="">Select Bed Type</option>
                        <?php foreach ($bed_types as $bt): ?>
                        <option value="<?= htmlspecialchars($bt, ENT_QUOTES, 'UTF-8') ?>"
                                <?= ($isEdit && ($room->bed_type ?? '') === $bt) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($bt, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr>
            <h6 class="text-muted mb-3"><i class="fas fa-concierge-bell"></i> Features</h6>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="amenities" class="form-label">Amenities</label>
                    <input type="text" name="amenities" id="amenities" class="form-control"
                           placeholder="WiFi, TV, Mini Bar, Safe"
                           value="<?php
                               if ($isEdit && !empty($room->amenities)) {
                                   $decoded = json_decode($room->amenities, true);
                                   if (is_array($decoded)) {
                                       echo htmlspecialchars(implode(', ', $decoded), ENT_QUOTES, 'UTF-8');
                                   } else {
                                       echo htmlspecialchars($room->amenities, ENT_QUOTES, 'UTF-8');
                                   }
                               }
                           ?>">
                    <small class="form-text text-muted">Separate with commas</small>
                </div>
                <div class="col-md-3">
                    <label for="square_feet" class="form-label">Square Feet</label>
                    <input type="number" name="square_feet" id="square_feet" class="form-control"
                           min="0"
                           value="<?= htmlspecialchars($isEdit ? ($room->square_feet ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3">
                    <label for="view_type" class="form-label">View Type</label>
                    <input type="text" name="view_type" id="view_type" class="form-control"
                           placeholder="City, Sea View, Skyline"
                           value="<?= htmlspecialchars($isEdit ? ($room->view_type ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <hr>
            <div class="text-end">
                <a href="/admin/rooms" class="btn btn-outline-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> <?= $isEdit ? 'Update Room' : 'Add Room' ?>
                </button>
            </div>
        </form>
    </div>
</div>
