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

<?php
    $checkIn = $booking['check_in'] ?? '';
    $checkOut = $booking['check_out'] ?? '';
    $nights = 0;
    if ($checkIn && $checkOut) {
        $diff = strtotime($checkOut) - strtotime($checkIn);
        if ($diff > 0) {
            $nights = (int)($diff / 86400);
        }
    }

    $bStatus = $booking['status'] ?? 'unknown';
    $statusClass = match($bStatus) {
        'confirmed' => 'success',
        'pending' => 'warning',
        'checked_in' => 'info',
        'checked_out' => 'secondary',
        'cancelled' => 'danger',
        'no_show' => 'dark',
        default => 'light text-dark',
    };

    $payment = $booking['payment_status'] ?? 'unknown';
    $payClass = match($payment) {
        'paid' => 'success',
        'pending' => 'warning',
        'refunded' => 'info',
        'failed' => 'danger',
        'partial' => 'primary',
        'unpaid' => 'secondary',
        default => 'secondary',
    };
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-calendar-check"></i> Booking Details</h4>
    <a href="/admin/bookings" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
</div>

<div class="row">
    <!-- Left Column: Booking Details -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <!-- Booking Reference -->
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h5 class="text-muted mb-1">Booking Reference</h5>
                        <h3 class="mb-0" style="font-family: monospace; letter-spacing: 1px;">
                            <?= htmlspecialchars($booking['booking_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </h3>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-<?= $statusClass ?> fs-6 me-1">
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $bStatus)), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="badge bg-<?= $payClass ?> fs-6">
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $payment)), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>

                <hr>

                <!-- Guest Information -->
                <h6 class="text-muted mb-3"><i class="fas fa-user"></i> Guest Information</h6>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <strong>Name</strong><br>
                        <?= htmlspecialchars($booking['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Email</strong><br>
                        <?= htmlspecialchars($booking['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Phone</strong><br>
                        <?= htmlspecialchars($booking['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <!-- Room Information -->
                <h6 class="text-muted mb-3"><i class="fas fa-bed"></i> Room Information</h6>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <strong>Room Number</strong><br>
                        <?= htmlspecialchars($booking['room_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Room Type</strong><br>
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $booking['room_type'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Floor</strong><br>
                        <?= (int)($booking['floor_number'] ?? 0) ?>
                    </div>
                </div>

                <!-- Stay Details -->
                <h6 class="text-muted mb-3"><i class="fas fa-calendar-alt"></i> Stay Details</h6>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <strong>Check-in</strong><br>
                        <?= htmlspecialchars($checkIn, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Check-out</strong><br>
                        <?= htmlspecialchars($checkOut, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Nights</strong><br>
                        <?= $nights ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Guests</strong><br>
                        <?= (int)($booking['num_guests'] ?? 1) ?>
                    </div>
                </div>

                <!-- Special Requests -->
                <?php if (!empty($booking['special_requests'])): ?>
                <h6 class="text-muted mb-3"><i class="fas fa-comment-alt"></i> Special Requests</h6>
                <div class="mb-4 p-3 bg-light rounded">
                    <?= htmlspecialchars($booking['special_requests'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php endif; ?>

                <hr>

                <!-- Pricing -->
                <h6 class="text-muted mb-3"><i class="fas fa-rupee-sign"></i> Pricing</h6>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <strong>Base Price</strong><br>
                        &#8377;<?= number_format((float)($booking['base_price'] ?? 0)) ?>
                    </div>
                    <div class="col-md-4">
                        <strong>GST</strong><br>
                        &#8377;<?= number_format((float)($booking['gst_amount'] ?? 0)) ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Total</strong><br>
                        <span class="fs-5 fw-bold">&#8377;<?= number_format((float)($booking['total_price'] ?? 0)) ?></span>
                    </div>
                </div>

                <hr>

                <!-- Metadata -->
                <div class="row text-muted">
                    <div class="col-md-4">
                        <small><strong>Hotel:</strong> <?= htmlspecialchars($booking['hotel_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <div class="col-md-4">
                        <small><strong>Created:</strong> <?= htmlspecialchars($booking['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                    <div class="col-md-4">
                        <small><strong>Username:</strong> <?= htmlspecialchars($booking['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                </div>

                <?php if ($bStatus === 'cancelled'): ?>
                <hr>
                <div class="alert alert-danger mb-0">
                    <h6 class="alert-heading"><i class="fas fa-ban"></i> Cancellation Details</h6>
                    <?php if (!empty($booking['cancelled_at'])): ?>
                    <p class="mb-1"><strong>Cancelled at:</strong> <?= htmlspecialchars($booking['cancelled_at'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if (!empty($booking['cancellation_reason'])): ?>
                    <p class="mb-0"><strong>Reason:</strong> <?= htmlspecialchars($booking['cancellation_reason'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Actions -->
    <div class="col-lg-4">
        <!-- Update Status -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-exchange-alt"></i> Update Status</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/bookings/status/<?= (int)$booking['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <select name="new_status" class="form-select mb-2">
                        <option value="pending" <?= $bStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $bStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="checked_in" <?= $bStatus === 'checked_in' ? 'selected' : '' ?>>Checked In</option>
                        <option value="checked_out" <?= $bStatus === 'checked_out' ? 'selected' : '' ?>>Checked Out</option>
                        <option value="cancelled" <?= $bStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-primary w-100">Update Status</button>
                </form>
            </div>
        </div>

        <!-- Update Payment -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-rupee-sign"></i> Update Payment</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/bookings/payment/<?= (int)$booking['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <select name="new_payment_status" class="form-select mb-2">
                        <option value="unpaid" <?= $payment === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                        <option value="partial" <?= $payment === 'partial' ? 'selected' : '' ?>>Partial</option>
                        <option value="paid" <?= $payment === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="refunded" <?= $payment === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                    </select>
                    <button type="submit" class="btn btn-success w-100">Update Payment</button>
                </form>
            </div>
        </div>

        <!-- Back Link -->
        <a href="/admin/bookings" class="btn btn-outline-secondary w-100">
            <i class="fas fa-arrow-left"></i> Back to All Bookings
        </a>
    </div>
</div>
