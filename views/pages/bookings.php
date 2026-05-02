<?php
    $upcomingCount  = count($upcoming_bookings);
    $pastCount      = count($past_bookings);
    $cancelledCount = count($cancelled_bookings);
    $totalCount     = $upcomingCount + $pastCount + $cancelledCount;

    /**
     * Render a booking card. Shared across all tabs.
     * @param array  $booking     Associative array from findByUser()
     * @param string $csrf_token  CSRF token for cancel forms
     * @param string $variant     'upcoming' | 'past' | 'cancelled'
     */
    function renderBookingCard(array $booking, string $csrf_token, string $variant): void
    {
        $statusClass = match($booking['status'] ?? '') {
            'pending'     => 'warning',
            'confirmed'   => 'success',
            'checked_in'  => 'primary',
            'checked_out' => 'info',
            'cancelled'   => 'secondary',
            'no_show'     => 'dark',
            default       => 'secondary',
        };
        $paymentClass = match($booking['payment_status'] ?? '') {
            'unpaid'   => 'danger',
            'partial'  => 'warning',
            'paid'     => 'success',
            'refunded' => 'info',
            default    => 'secondary',
        };

        $isCancellable = in_array($booking['status'] ?? '', ['pending', 'confirmed'], true);
        $cardOpacity   = $variant === 'cancelled' ? 'opacity-75' : '';
        $collapseId    = 'sr-' . (int)$booking['id'];
?>
        <div class="card shadow-sm mb-3 <?= $cardOpacity ?>">
            <div class="card-body">
                <div class="row align-items-center">
                    <!-- Reference & Room -->
                    <div class="col-md-3">
                        <span class="d-block fw-bold font-monospace mb-1"><?= htmlspecialchars($booking['booking_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="badge bg-info"><?= htmlspecialchars(ucfirst($booking['room_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="text-muted small ms-1">Room #<?= htmlspecialchars($booking['room_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-hotel"></i> <?= htmlspecialchars($booking['hotel_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </small>
                    </div>

                    <!-- Dates -->
                    <div class="col-md-3">
                        <div class="mb-1">
                            <i class="fas fa-sign-in-alt text-success"></i>
                            <strong><?= htmlspecialchars(date('D, M d, Y', strtotime($booking['check_in'])), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div>
                            <i class="fas fa-sign-out-alt text-danger"></i>
                            <strong><?= htmlspecialchars(date('D, M d, Y', strtotime($booking['check_out'])), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <small class="text-muted">
                            <?php
                                $n = (int)(new \DateTime($booking['check_in']))->diff(new \DateTime($booking['check_out']))->days;
                            ?>
                            <?= $n ?> night<?= $n !== 1 ? 's' : '' ?>
                            &middot;
                            <i class="fas fa-users"></i> <?= (int)$booking['num_guests'] ?> guest<?= (int)$booking['num_guests'] !== 1 ? 's' : '' ?>
                        </small>
                    </div>

                    <!-- Status & Payment -->
                    <div class="col-md-2 text-center">
                        <span class="badge bg-<?= $statusClass ?> mb-1">
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $booking['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <br>
                        <span class="badge bg-<?= $paymentClass ?>">
                            <?= htmlspecialchars(ucfirst($booking['payment_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <!-- Price -->
                    <div class="col-md-2 text-center">
                        <span class="h5 text-primary mb-0">&#8377;<?= number_format((float)($booking['total_price'] ?? 0)) ?></span>
                    </div>

                    <!-- Actions -->
                    <div class="col-md-2 text-end">
                        <?php if ($variant === 'upcoming' && $isCancellable): ?>
                            <form method="POST" action="/booking/<?= (int)$booking['id'] ?>/cancel"
                                  onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-times"></i> Cancel Booking
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Special Requests (collapsible) -->
                <?php if (!empty($booking['special_requests'])): ?>
                    <div class="mt-2">
                        <a class="text-muted small text-decoration-none" data-bs-toggle="collapse" href="#<?= $collapseId ?>" role="button">
                            <i class="fas fa-sticky-note"></i> Special Requests <i class="fas fa-chevron-down fa-xs"></i>
                        </a>
                        <div class="collapse" id="<?= $collapseId ?>">
                            <div class="card card-body bg-light mt-1 py-2 px-3 small">
                                <?= htmlspecialchars($booking['special_requests'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Cancellation details -->
                <?php if ($variant === 'cancelled'): ?>
                    <div class="mt-2 small text-muted">
                        <i class="fas fa-ban"></i>
                        Cancelled
                        <?php if (!empty($booking['cancelled_at'])): ?>
                            on <?= htmlspecialchars(date('M d, Y \a\t g:i A', strtotime($booking['cancelled_at'])), ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                        <?php if (!empty($booking['cancellation_reason'])): ?>
                            &mdash; <?= htmlspecialchars($booking['cancellation_reason'], ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
<?php
    }
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">My Bookings</li>
    </ol>
</nav>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-calendar-check"></i> My Bookings <span class="badge bg-secondary fs-6 align-middle"><?= $totalCount ?></span></h2>
    <a href="/rooms" class="btn btn-primary">
        <i class="fas fa-plus"></i> Book a Room
    </a>
</div>

<?php if ($totalCount === 0): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-suitcase-rolling fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No bookings yet</h5>
            <p class="text-muted mb-3">Browse our rooms and book your perfect stay!</p>
            <a href="/rooms" class="btn btn-primary">
                <i class="fas fa-search"></i> Browse Rooms
            </a>
        </div>
    </div>
<?php else: ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="bookingTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab">
                <i class="fas fa-clock"></i> Upcoming <span class="badge bg-primary"><?= $upcomingCount ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab">
                <i class="fas fa-history"></i> Past <span class="badge bg-secondary"><?= $pastCount ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled" type="button" role="tab">
                <i class="fas fa-ban"></i> Cancelled <span class="badge bg-secondary"><?= $cancelledCount ?></span>
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="bookingTabsContent">

        <!-- Upcoming -->
        <div class="tab-pane fade show active" id="upcoming" role="tabpanel">
            <?php if ($upcomingCount === 0): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-calendar-day fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-2">No upcoming bookings.</p>
                        <a href="/rooms" class="btn btn-outline-primary btn-sm">Browse Rooms</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($upcoming_bookings as $booking): ?>
                    <?php renderBookingCard($booking, $csrf_token, 'upcoming'); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Past -->
        <div class="tab-pane fade" id="past" role="tabpanel">
            <?php if ($pastCount === 0): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-history fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No past bookings.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($past_bookings as $booking): ?>
                    <?php renderBookingCard($booking, $csrf_token, 'past'); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Cancelled -->
        <div class="tab-pane fade" id="cancelled" role="tabpanel">
            <?php if ($cancelledCount === 0): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p class="text-muted mb-0">No cancelled bookings. Great!</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($cancelled_bookings as $booking): ?>
                    <?php renderBookingCard($booking, $csrf_token, 'cancelled'); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Summary Stats -->
    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <h6 class="text-muted">Total</h6>
                    <p class="h4 mb-0"><?= $totalCount ?></p>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">Upcoming</h6>
                    <p class="h4 mb-0 text-primary"><?= $upcomingCount ?></p>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">Completed</h6>
                    <p class="h4 mb-0 text-success"><?= $pastCount ?></p>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">Cancelled</h6>
                    <p class="h4 mb-0 text-secondary"><?= $cancelledCount ?></p>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>
