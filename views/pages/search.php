<h2 class="mb-4">Search Rooms</h2>

<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form action="/search" method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="query" class="form-control form-control-lg"
                       placeholder="Search by city, hotel name, or room type..."
                       value="<?= htmlspecialchars($query ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       autofocus>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($query): ?>
    <h4 class="mb-3">Search Results for: "<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>"</h4>

    <?php if (empty($rooms)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No rooms found matching your search criteria.
        </div>
    <?php else: ?>
        <p class="text-muted">Found <?= count($rooms) ?> room(s)</p>

        <div class="row">
            <?php foreach ($rooms as $room): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($room['hotel_name'] ?? 'Hotel', ENT_QUOTES, 'UTF-8') ?></h5>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($room['city'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="card-text">
                                <strong><?= htmlspecialchars(ucfirst($room['room_type']), ENT_QUOTES, 'UTF-8') ?> - Room <?= htmlspecialchars($room['room_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </p>
                            <p class="card-text">
                                <i class="fas fa-users"></i> Max: <?= (int)$room['max_occupancy'] ?> guests<br>
                                <i class="fas fa-bed"></i> <?= (int)$room['num_beds'] ?> bed(s)
                            </p>
                            <p class="card-text">
                                <span class="h5 text-primary">$<?= number_format($room['base_price'], 2) ?></span>
                                <small class="text-muted">/ night</small>
                            </p>
                        </div>
                        <div class="card-footer">
                            <?php if ($user): ?>
                                <a href="/book/<?= (int)$room['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-calendar-check"></i> Book Now
                                </a>
                            <?php else: ?>
                                <a href="/login?redirect=<?= urlencode('/book/' . (int)$room['id']) ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-sign-in-alt"></i> Login to Book
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-search"></i> Enter a search term to find available rooms.
    </div>
<?php endif; ?>
