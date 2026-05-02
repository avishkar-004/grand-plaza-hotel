<!-- Hero Section -->
<div class="bg-primary text-white rounded-3 p-5 mb-5">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <h1 class="display-4 fw-bold">Welcome to Grand Plaza Hotel & Resort</h1>
            <p class="lead mb-3">Luxury Redefined on Marine Drive, Mumbai</p>
            <?php if ($hotel): ?>
                <p class="mb-4">
                    <i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($hotel->getFullAddress(), ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>
            <div class="d-flex gap-3">
                <a href="/rooms" class="btn btn-light btn-lg">
                    <i class="fas fa-door-open me-2"></i>Discover Our Rooms
                </a>
                <?php if ($user): ?>
                    <a href="/rooms" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-calendar-check me-2"></i>Book Your Stay
                    </a>
                <?php else: ?>
                    <a href="/register" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Register to Book
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4 text-center d-none d-lg-block">
            <?php if ($hotel && $hotel->star_rating): ?>
                <div class="display-1 mb-2"><?= htmlspecialchars($hotel->getStarRating(), ENT_QUOTES, 'UTF-8') ?></div>
                <p class="lead"><?= (int)$hotel->star_rating ?>-Star Luxury Hotel</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Hotel Highlights -->
<section class="mb-5">
    <h2 class="mb-4"><i class="fas fa-hotel me-2 text-primary"></i>About Our Hotel</h2>
    <div class="row">
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Grand Plaza Hotel & Resort</h5>
                    <?php if ($hotel && $hotel->description): ?>
                        <p class="card-text"><?= htmlspecialchars($hotel->description, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <p class="card-text">Experience world-class hospitality with breathtaking views of the Arabian Sea. Nestled along the iconic Marine Drive, Grand Plaza offers an unparalleled blend of luxury, comfort, and Indian warmth.</p>
                    <?php endif; ?>
                    <ul class="list-unstyled">
                        <?php if ($hotel): ?>
                            <li class="mb-2"><i class="fas fa-map-marker-alt text-primary me-2"></i><?= htmlspecialchars($hotel->getFullAddress(), ENT_QUOTES, 'UTF-8') ?></li>
                            <?php if ($hotel->phone): ?>
                                <li class="mb-2"><i class="fas fa-phone text-primary me-2"></i><?= htmlspecialchars($hotel->phone, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endif; ?>
                            <?php if ($hotel->email): ?>
                                <li class="mb-2"><i class="fas fa-envelope text-primary me-2"></i><?= htmlspecialchars($hotel->email, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm text-center">
                <div class="card-body d-flex flex-column justify-content-center">
                    <i class="fas fa-sign-in-alt fa-2x text-primary mb-3"></i>
                    <h5>Check-In</h5>
                    <?php if ($hotel): ?>
                        <p class="display-6 fw-bold text-primary"><?= htmlspecialchars(date('g:i A', strtotime($hotel->check_in_time)), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <p class="display-6 fw-bold text-primary">3:00 PM</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-0 shadow-sm text-center">
                <div class="card-body d-flex flex-column justify-content-center">
                    <i class="fas fa-sign-out-alt fa-2x text-primary mb-3"></i>
                    <h5>Check-Out</h5>
                    <?php if ($hotel): ?>
                        <p class="display-6 fw-bold text-primary"><?= htmlspecialchars(date('g:i A', strtotime($hotel->check_out_time)), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <p class="display-6 fw-bold text-primary">11:00 AM</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Amenities -->
<?php if ($hotel): ?>
<?php $amenities = $hotel->getAmenities(); ?>
<?php if (!empty($amenities)): ?>
<section class="mb-5">
    <h2 class="mb-4"><i class="fas fa-concierge-bell me-2 text-primary"></i>Hotel Amenities</h2>
    <div class="row">
        <?php
        $amenityIcons = [
            'WiFi' => 'fa-wifi', 'Pool' => 'fa-swimming-pool', 'Gym' => 'fa-dumbbell',
            'Spa' => 'fa-spa', 'Restaurant' => 'fa-utensils', 'Bar' => 'fa-cocktail',
            'Room Service' => 'fa-bell-concierge', 'Parking' => 'fa-parking',
            'Laundry' => 'fa-shirt', 'Business Center' => 'fa-briefcase',
            'Conference Room' => 'fa-people-roof', 'Airport Shuttle' => 'fa-van-shuttle',
            'Concierge' => 'fa-bell-concierge', 'Valet Parking' => 'fa-car',
        ];
        ?>
        <?php foreach ($amenities as $amenity): ?>
            <div class="col-6 col-md-3 mb-3">
                <div class="card border-0 bg-light text-center py-3">
                    <i class="fas <?= $amenityIcons[$amenity] ?? 'fa-check-circle' ?> fa-2x text-primary mb-2"></i>
                    <small class="fw-semibold"><?= htmlspecialchars($amenity, ENT_QUOTES, 'UTF-8') ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php endif; ?>

<!-- Discover Our Rooms -->
<?php if (!empty($rooms)): ?>
<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-bed me-2 text-primary"></i>Discover Our Rooms</h2>
        <a href="/rooms" class="btn btn-outline-primary">View All Rooms <i class="fas fa-arrow-right ms-1"></i></a>
    </div>
    <div class="row">
        <?php foreach ($rooms as $room): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($room->getFormattedType(), ENT_QUOTES, 'UTF-8') ?></h5>
                            <span class="badge bg-primary">Room <?= htmlspecialchars($room->room_number, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <?php if ($room->description): ?>
                            <p class="card-text text-muted small"><?= htmlspecialchars($room->description, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <ul class="list-unstyled small mb-3">
                            <li><i class="fas fa-users text-muted me-1"></i> Max Occupancy: <?= (int)$room->max_occupancy ?></li>
                            <li><i class="fas fa-bed text-muted me-1"></i> <?= (int)$room->num_beds ?> <?= htmlspecialchars($room->bed_type ?? 'Bed', ENT_QUOTES, 'UTF-8') ?></li>
                            <?php if ($room->view_type): ?>
                                <li><i class="fas fa-mountain text-muted me-1"></i> <?= htmlspecialchars($room->view_type, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endif; ?>
                            <?php if ($room->square_feet): ?>
                                <li><i class="fas fa-ruler-combined text-muted me-1"></i> <?= (int)$room->square_feet ?> sq ft</li>
                            <?php endif; ?>
                        </ul>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h4 text-primary mb-0">&#8377;<?= number_format($room->base_price) ?></span>
                            <small class="text-muted">/ night</small>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <div class="d-grid gap-2">
                            <a href="/room/<?= (int)$room->id ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                            <?php if ($user): ?>
                                <a href="/book/<?= (int)$room->id ?>" class="btn btn-primary btn-sm">Book Your Stay</a>
                            <?php else: ?>
                                <a href="/login" class="btn btn-outline-secondary btn-sm">Login to Book</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Quick Stats -->
<div class="row mt-4">
    <div class="col-md-3 text-center mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h3 class="text-primary"><?= $totalRooms ?? count($rooms) ?></h3>
                <p class="mb-0">Luxury Rooms</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 text-center mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h3 class="text-primary">24/7</h3>
                <p class="mb-0">Front Desk & Concierge</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 text-center mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h3 class="text-primary"><?= $hotel ? (int)$hotel->star_rating : 5 ?> Star</h3>
                <p class="mb-0">Hotel Rating</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 text-center mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h3 class="text-primary">100%</h3>
                <p class="mb-0">Guest Satisfaction</p>
            </div>
        </div>
    </div>
</div>
