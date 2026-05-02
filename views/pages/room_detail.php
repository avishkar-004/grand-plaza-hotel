<?php
$typeBadgeColors = [
    'single'        => 'info',
    'double'        => 'primary',
    'suite'         => 'warning',
    'deluxe'        => 'success',
    'presidential'  => 'danger',
];
$typeGradients = [
    'single'        => 'linear-gradient(135deg, #17a2b8 0%, #138496 100%)',
    'double'        => 'linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%)',
    'suite'         => 'linear-gradient(135deg, #ffc107 0%, #e0a800 100%)',
    'deluxe'        => 'linear-gradient(135deg, #198754 0%, #146c43 100%)',
    'presidential'  => 'linear-gradient(135deg, #dc3545 0%, #b02a37 100%)',
];

$rType = $room['room_type'] ?? 'single';
$badgeColor = $typeBadgeColors[$rType] ?? 'secondary';
$gradient = $typeGradients[$rType] ?? $typeGradients['single'];

$amenities = json_decode($room['amenities'] ?? '[]', true);
if (!is_array($amenities)) {
    $amenities = [];
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <li class="breadcrumb-item"><a href="/rooms">Rooms</a></li>
        <li class="breadcrumb-item active" aria-current="page">Room <?= htmlspecialchars($room['room_number'], ENT_QUOTES, 'UTF-8') ?></li>
    </ol>
</nav>

<div class="row">
    <!-- Left column -->
    <div class="col-lg-8">
        <!-- Hero image placeholder -->
        <div class="rounded-3 mb-4 position-relative" style="background:<?= $gradient ?>;height:280px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-bed fa-5x text-white opacity-50"></i>
            <span class="position-absolute top-0 end-0 m-3 badge bg-<?= htmlspecialchars($badgeColor, ENT_QUOTES, 'UTF-8') ?> fs-6">
                <?= htmlspecialchars(ucfirst($rType), ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>

        <!-- Room title -->
        <h3 class="mb-1">
            <?= htmlspecialchars(ucfirst($rType), ENT_QUOTES, 'UTF-8') ?> Room &mdash; #<?= htmlspecialchars($room['room_number'], ENT_QUOTES, 'UTF-8') ?>
        </h3>
        <p class="text-muted mb-4">
            <i class="fas fa-building"></i> <?= htmlspecialchars($room['hotel_name'], ENT_QUOTES, 'UTF-8') ?>
            &middot;
            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($room['city'], ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($room['address'])): ?>
                &middot; <?= htmlspecialchars($room['address'], ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
            &middot; Floor <?= (int)$room['floor_number'] ?>
        </p>

        <!-- Description -->
        <?php if (!empty($room['description'])): ?>
            <div class="mb-4">
                <h5>About This Room</h5>
                <p><?= htmlspecialchars($room['description'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>

        <!-- Amenities -->
        <?php if (!empty($amenities)): ?>
            <div class="mb-4">
                <h5><i class="fas fa-concierge-bell"></i> Amenities</h5>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php foreach ($amenities as $amenity): ?>
                        <span class="badge bg-success p-2">
                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($amenity, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Room specs grid -->
        <div class="mb-4">
            <h5><i class="fas fa-info-circle"></i> Room Details</h5>
            <div class="row g-3 mt-1">
                <div class="col-6 col-md-4">
                    <div class="card text-center h-100">
                        <div class="card-body py-3">
                            <i class="fas fa-users fa-lg text-primary mb-1"></i>
                            <p class="fw-bold mb-0"><?= (int)$room['max_occupancy'] ?> Guests</p>
                            <small class="text-muted">Max Occupancy</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card text-center h-100">
                        <div class="card-body py-3">
                            <i class="fas fa-bed fa-lg text-primary mb-1"></i>
                            <p class="fw-bold mb-0"><?= (int)$room['num_beds'] ?> <?= htmlspecialchars(ucfirst($room['bed_type'] ?? 'Bed'), ENT_QUOTES, 'UTF-8') ?></p>
                            <small class="text-muted">Beds</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card text-center h-100">
                        <div class="card-body py-3">
                            <i class="fas fa-layer-group fa-lg text-primary mb-1"></i>
                            <p class="fw-bold mb-0">Floor <?= (int)$room['floor_number'] ?></p>
                            <small class="text-muted">Level</small>
                        </div>
                    </div>
                </div>
                <?php if (!empty($room['square_feet'])): ?>
                    <div class="col-6 col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-body py-3">
                                <i class="fas fa-ruler-combined fa-lg text-primary mb-1"></i>
                                <p class="fw-bold mb-0"><?= (int)$room['square_feet'] ?> sq ft</p>
                                <small class="text-muted">Room Size</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($room['view_type'])): ?>
                    <div class="col-6 col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-body py-3">
                                <i class="fas fa-mountain fa-lg text-primary mb-1"></i>
                                <p class="fw-bold mb-0"><?= htmlspecialchars(ucfirst($room['view_type']), ENT_QUOTES, 'UTF-8') ?></p>
                                <small class="text-muted">View</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right column (sticky sidebar) -->
    <div class="col-lg-4">
        <div class="sticky-top" style="top:1rem;">
            <!-- Pricing card -->
            <div class="card shadow-sm mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-tag"></i> Pricing</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="text-muted">Base Rate</span>
                        <p class="h3 text-primary mb-0">&#8377;<?= number_format((float)$room['base_price']) ?></p>
                        <small class="text-muted">per night</small>
                    </div>

                    <?php if (!empty($room['weekend_price'])): ?>
                        <div class="mb-3">
                            <span class="text-muted">Weekend Rate</span>
                            <p class="h5 mb-0">&#8377;<?= number_format((float)$room['weekend_price']) ?></p>
                            <small class="text-muted">Fri &amp; Sat nights</small>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($room['peak_season_price'])): ?>
                        <div class="mb-3">
                            <span class="text-muted">Peak Season Rate</span>
                            <p class="h5 mb-0">&#8377;<?= number_format((float)$room['peak_season_price']) ?></p>
                            <small class="text-muted">Holiday periods</small>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <?php if ($hotel): ?>
                        <div class="small">
                            <p class="mb-1">
                                <i class="fas fa-sign-in-alt text-success"></i>
                                <strong>Check-in:</strong> <?= htmlspecialchars($hotel->check_in_time ?? '3:00 PM', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-sign-out-alt text-danger"></i>
                                <strong>Check-out:</strong> <?= htmlspecialchars($hotel->check_out_time ?? '11:00 AM', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Availability card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-calendar-alt"></i> Availability (Next 30 Days)</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($booked_dates)): ?>
                        <p class="text-success mb-0">
                            <i class="fas fa-check-circle"></i> No upcoming bookings &mdash; fully available!
                        </p>
                    <?php else: ?>
                        <p class="small text-muted mb-2">Booked dates:</p>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($booked_dates as $bd): ?>
                                <li class="mb-1">
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                        <i class="fas fa-ban"></i>
                                        <?= htmlspecialchars($bd['check_in'], ENT_QUOTES, 'UTF-8') ?>
                                        &rarr;
                                        <?= htmlspecialchars($bd['check_out'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Booking CTA -->
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <?php if ($user): ?>
                        <a href="/book/<?= (int)$room['id'] ?>" class="btn btn-primary btn-lg w-100 mb-2">
                            <i class="fas fa-calendar-check"></i> Book This Room
                        </a>
                        <small class="text-muted">Instant confirmation upon booking</small>
                    <?php else: ?>
                        <p class="text-muted mb-3">Sign in to book this room</p>
                        <a href="/login?redirect=<?= urlencode('/book/' . (int)$room['id']) ?>" class="btn btn-outline-primary btn-lg w-100">
                            <i class="fas fa-sign-in-alt"></i> Login to Book
                        </a>
                        <p class="mt-2 mb-0">
                            <small>Don't have an account? <a href="/register">Register here</a></small>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Back link -->
            <a href="/rooms" class="btn btn-outline-secondary w-100">
                <i class="fas fa-arrow-left"></i> Back to All Rooms
            </a>
        </div>
    </div>
</div>
