<?php
    $checkInTime = htmlspecialchars($hotel_times['check_in_time'] ?? '14:00', ENT_QUOTES, 'UTF-8');
    $checkOutTime = htmlspecialchars($hotel_times['check_out_time'] ?? '11:00', ENT_QUOTES, 'UTF-8');
    $preCheckIn = htmlspecialchars($prefilled_dates['check_in'] ?? '', ENT_QUOTES, 'UTF-8');
    $preCheckOut = htmlspecialchars($prefilled_dates['check_out'] ?? '', ENT_QUOTES, 'UTF-8');
    $amenities = json_decode($room['amenities'] ?? '[]', true);
    if (!is_array($amenities)) { $amenities = []; }
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <li class="breadcrumb-item"><a href="/rooms">Rooms</a></li>
        <li class="breadcrumb-item">
            <a href="/room/<?= (int)$room['id'] ?>">Room #<?= htmlspecialchars($room['room_number'], ENT_QUOTES, 'UTF-8') ?></a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Book</li>
    </ol>
</nav>

<div class="row">
    <!-- Left column: Booking Form -->
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-calendar-check"></i> Book Your Stay
                </h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/book" id="bookingForm">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">

                    <!-- Date Selection -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="check_in" class="form-label">
                                <i class="fas fa-sign-in-alt text-primary"></i> Check-in Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="check_in" name="check_in"
                                   min="<?= date('Y-m-d') ?>"
                                   value="<?= $preCheckIn ?>"
                                   required>
                            <div class="form-text">Check-in from <?= $checkInTime ?></div>
                        </div>
                        <div class="col-md-6">
                            <label for="check_out" class="form-label">
                                <i class="fas fa-sign-out-alt text-primary"></i> Check-out Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="check_out" name="check_out"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                   value="<?= $preCheckOut ?>"
                                   required>
                            <div class="form-text">Check-out by <?= $checkOutTime ?></div>
                        </div>
                    </div>

                    <!-- Number of Guests -->
                    <div class="mb-3">
                        <label for="num_guests" class="form-label">
                            <i class="fas fa-users text-primary"></i> Number of Guests <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="num_guests" name="num_guests"
                               min="1" max="<?= (int)$room['max_occupancy'] ?>" value="1" required>
                        <div class="form-text">Maximum occupancy: <?= (int)$room['max_occupancy'] ?> guest(s)</div>
                    </div>

                    <!-- Special Requests -->
                    <div class="mb-4">
                        <label for="special_requests" class="form-label">
                            <i class="fas fa-concierge-bell text-primary"></i> Special Requests
                        </label>
                        <textarea class="form-control" id="special_requests" name="special_requests"
                                  rows="3" maxlength="500"
                                  placeholder="Early check-in, extra pillows, dietary needs, etc."></textarea>
                        <div class="form-text">Optional &mdash; up to 500 characters</div>
                    </div>

                    <!-- Price Estimate -->
                    <div id="price-estimate" class="card bg-light mb-4" style="display: none;">
                        <div class="card-body">
                            <h6 class="card-title mb-3"><i class="fas fa-calculator"></i> Price Estimate</h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Room rate:</span>
                                <span>&#8377;<?= number_format((float)$room['base_price']) ?> / night</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Nights:</span>
                                <span id="est-nights">--</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Subtotal:</span>
                                <span>&#8377;<span id="est-subtotal">--</span></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>GST (18%):</span>
                                <span>&#8377;<span id="est-tax">--</span></span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total:</span>
                                <span class="text-primary">&#8377;<span id="est-total">--</span></span>
                            </div>
                            <p class="text-muted small mt-2 mb-0">
                                <i class="fas fa-info-circle"></i> Prices inclusive of applicable GST
                            </p>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-check-circle"></i> Confirm Booking
                    </button>
                </form>
            </div>
        </div>

        <!-- Back to room details -->
        <a href="/room/<?= (int)$room['id'] ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Room Details
        </a>
    </div>

    <!-- Right column: Room Summary -->
    <div class="col-lg-4">
        <!-- Room Info Card -->
        <div class="card shadow-sm mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-door-open"></i> Room Summary</h5>
            </div>
            <div class="card-body">
                <!-- Room type & number -->
                <div class="mb-3">
                    <span class="badge bg-info fs-6"><?= htmlspecialchars(ucfirst($room['room_type']), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="ms-1 text-muted">Room #<?= htmlspecialchars($room['room_number'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <!-- Hotel -->
                <h6 class="text-muted mb-1">Hotel</h6>
                <p class="mb-2">
                    <i class="fas fa-hotel text-primary"></i>
                    <strong><?= htmlspecialchars($room['hotel_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                </p>

                <!-- Location -->
                <h6 class="text-muted mb-1">Location</h6>
                <p class="mb-2">
                    <i class="fas fa-map-marker-alt text-danger"></i>
                    <?= htmlspecialchars($room['city'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <?php if (!empty($room['address'])): ?>
                    <p class="small text-muted mb-2"><?= htmlspecialchars($room['address'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <!-- Capacity & Beds -->
                <h6 class="text-muted mb-1">Capacity</h6>
                <p class="mb-2">
                    <i class="fas fa-users text-primary"></i> <?= (int)$room['max_occupancy'] ?> guest(s)
                    <?php if (!empty($room['num_beds'])): ?>
                        &nbsp;&middot;&nbsp;
                        <i class="fas fa-bed text-primary"></i> <?= (int)$room['num_beds'] ?> bed(s)
                        <?php if (!empty($room['bed_type'])): ?>
                            (<?= htmlspecialchars(ucfirst($room['bed_type']), ENT_QUOTES, 'UTF-8') ?>)
                        <?php endif; ?>
                    <?php endif; ?>
                </p>

                <!-- Amenities preview -->
                <?php if (!empty($amenities)): ?>
                    <h6 class="text-muted mb-1">Amenities</h6>
                    <p class="mb-2">
                        <?php foreach (array_slice($amenities, 0, 5) as $amenity): ?>
                            <span class="badge bg-success me-1 mb-1">
                                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($amenity, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endforeach; ?>
                        <?php if (count($amenities) > 5): ?>
                            <span class="badge bg-secondary">+<?= count($amenities) - 5 ?> more</span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <!-- Check-in / Check-out times -->
                <h6 class="text-muted mb-1">Hotel Schedule</h6>
                <p class="mb-0 small">
                    <i class="fas fa-clock text-primary"></i>
                    Check-in: <strong><?= $checkInTime ?></strong>
                    &nbsp;&middot;&nbsp;
                    Check-out: <strong><?= $checkOutTime ?></strong>
                </p>

                <hr>

                <!-- Price -->
                <h6 class="text-muted mb-1">Price Per Night</h6>
                <p class="h3 text-primary mb-0">&#8377;<?= number_format((float)$room['base_price']) ?></p>
                <small class="text-muted">+ applicable GST</small>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var checkInEl  = document.getElementById('check_in');
    var checkOutEl = document.getElementById('check_out');
    var estimateEl = document.getElementById('price-estimate');
    var estNights   = document.getElementById('est-nights');
    var estSubtotal = document.getElementById('est-subtotal');
    var estTax      = document.getElementById('est-tax');
    var estTotal    = document.getElementById('est-total');
    var pricePerNight = <?= (float)$room['base_price'] ?>;

    function formatIndian(num) {
        var str = Math.round(num).toString();
        var lastThree = str.substring(str.length - 3);
        var otherNumbers = str.substring(0, str.length - 3);
        if (otherNumbers !== '') {
            lastThree = ',' + lastThree;
        }
        return otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree;
    }

    function updateEstimate() {
        var ci = checkInEl.value;
        var co = checkOutEl.value;

        if (!ci || !co) {
            estimateEl.style.display = 'none';
            return;
        }

        var d1 = new Date(ci + 'T00:00:00');
        var d2 = new Date(co + 'T00:00:00');
        var nights = Math.round((d2 - d1) / 86400000);

        if (nights <= 0 || nights > 30) {
            estimateEl.style.display = 'none';
            return;
        }

        var subtotal = pricePerNight * nights;
        var tax      = subtotal * 0.18;
        var total    = subtotal + tax;

        estNights.textContent   = nights;
        estSubtotal.textContent = formatIndian(subtotal);
        estTax.textContent      = formatIndian(tax);
        estTotal.textContent    = formatIndian(total);
        estimateEl.style.display = 'block';
    }

    // Keep check_out min in sync with check_in
    checkInEl.addEventListener('change', function() {
        if (this.value) {
            var next = new Date(this.value + 'T00:00:00');
            next.setDate(next.getDate() + 1);
            var y = next.getFullYear();
            var m = String(next.getMonth() + 1).padStart(2, '0');
            var d = String(next.getDate()).padStart(2, '0');
            var minOut = y + '-' + m + '-' + d;
            checkOutEl.setAttribute('min', minOut);
            if (checkOutEl.value && checkOutEl.value <= this.value) {
                checkOutEl.value = minOut;
            }
        }
        updateEstimate();
    });

    checkOutEl.addEventListener('change', updateEstimate);

    // Calculate on load if dates are pre-filled
    updateEstimate();
})();
</script>
