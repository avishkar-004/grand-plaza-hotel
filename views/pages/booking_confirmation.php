<div class="container py-5">
    <div class="text-center mb-4">
        <div class="mb-3">
            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
        </div>
        <h1 class="h2">Booking Confirmed!</h1>
        <p class="text-muted">Your reservation has been successfully made</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Booking Reference Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Booking Reference: <span class="font-monospace"><?= htmlspecialchars($booking['booking_reference'], ENT_QUOTES, 'UTF-8') ?></span></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Hotel</h6>
                            <p><?= htmlspecialchars($booking['hotel_name'], ENT_QUOTES, 'UTF-8') ?><br>
                            <small class="text-muted"><?= htmlspecialchars($booking['address'] . ', ' . $booking['city'], ENT_QUOTES, 'UTF-8') ?></small></p>

                            <h6>Room</h6>
                            <p><?= htmlspecialchars(ucfirst($booking['room_type']), ENT_QUOTES, 'UTF-8') ?> - #<?= htmlspecialchars($booking['room_number'], ENT_QUOTES, 'UTF-8') ?><br>
                            <small class="text-muted">Floor <?= (int)$booking['floor_number'] ?> | <?= htmlspecialchars($booking['bed_type'], ENT_QUOTES, 'UTF-8') ?> bed</small></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Check-in</h6>
                            <p><?= date('D, d M Y', strtotime($booking['check_in'])) ?><br>
                            <small class="text-muted">After <?= htmlspecialchars($booking['check_in_time'] ?? '3:00 PM', ENT_QUOTES, 'UTF-8') ?></small></p>

                            <h6>Check-out</h6>
                            <p><?= date('D, d M Y', strtotime($booking['check_out'])) ?><br>
                            <small class="text-muted">Before <?= htmlspecialchars($booking['check_out_time'] ?? '11:00 AM', ENT_QUOTES, 'UTF-8') ?></small></p>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Guests:</strong> <?= (int)$booking['num_guests'] ?></p>
                            <?php if (!empty($booking['special_requests'])): ?>
                                <p><strong>Special Requests:</strong> <?= htmlspecialchars($booking['special_requests'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><td>Room charges (<?= (int)$nights ?> night<?= $nights !== 1 ? 's' : '' ?>)</td><td class="text-end">&#8377;<?= number_format($booking['base_price']) ?></td></tr>
                                <tr><td>GST</td><td class="text-end">&#8377;<?= number_format($booking['tax_amount']) ?></td></tr>
                                <?php if ($booking['discount_amount'] > 0): ?>
                                <tr><td>Discount</td><td class="text-end text-success">-&#8377;<?= number_format($booking['discount_amount']) ?></td></tr>
                                <?php endif; ?>
                                <tr class="fw-bold"><td>Total</td><td class="text-end">&#8377;<?= number_format($booking['total_price']) ?></td></tr>
                            </table>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i>
                        Please save your booking reference <strong><?= htmlspecialchars($booking['booking_reference'], ENT_QUOTES, 'UTF-8') ?></strong> for your records.
                        For any queries, contact us at <?= htmlspecialchars($booking['phone'] ?? '+91-22-6789-0100', ENT_QUOTES, 'UTF-8') ?>.
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="/bookings" class="btn btn-primary me-2"><i class="fas fa-list"></i> My Bookings</a>
                <a href="/rooms" class="btn btn-outline-primary"><i class="fas fa-bed"></i> Browse More Rooms</a>
                <button onclick="window.print()" class="btn btn-outline-secondary ms-2"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>
    </div>
</div>
