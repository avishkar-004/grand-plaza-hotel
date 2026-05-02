<?php
/**
 * Badge color per room type
 */
$typeBadgeColors = [
    'single'        => 'info',
    'double'        => 'primary',
    'suite'         => 'warning',
    'deluxe'        => 'success',
    'presidential'  => 'danger',
];

/**
 * Gradient for image placeholder per room type
 */
$typeGradients = [
    'single'        => 'linear-gradient(135deg, #17a2b8 0%, #138496 100%)',
    'double'        => 'linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%)',
    'suite'         => 'linear-gradient(135deg, #ffc107 0%, #e0a800 100%)',
    'deluxe'        => 'linear-gradient(135deg, #198754 0%, #146c43 100%)',
    'presidential'  => 'linear-gradient(135deg, #dc3545 0%, #b02a37 100%)',
];

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="fas fa-door-open"></i> Our Rooms</h2>
        <?php if ($hotel): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($hotel->name, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Filter / Search Form -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form action="/rooms" method="GET">
            <div class="row g-3">
                <div class="col-md-6 col-lg-2">
                    <label for="check_in" class="form-label">Check-in</label>
                    <input type="date" name="check_in" id="check_in" class="form-control"
                           min="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>"
                           value="<?= htmlspecialchars($filters['check_in'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="check_out" class="form-label">Check-out</label>
                    <input type="date" name="check_out" id="check_out" class="form-control"
                           min="<?= htmlspecialchars($tomorrow, ENT_QUOTES, 'UTF-8') ?>"
                           value="<?= htmlspecialchars($filters['check_out'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="type" class="form-label">Room Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach ($room_types as $rt): ?>
                            <option value="<?= htmlspecialchars($rt, ENT_QUOTES, 'UTF-8') ?>"
                                <?= ($filters['type'] ?? '') === $rt ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($rt), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-1">
                    <label for="guests" class="form-label">Guests</label>
                    <input type="number" name="guests" id="guests" class="form-control"
                           min="1" max="10" placeholder="Any"
                           value="<?= htmlspecialchars($filters['guests'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6 col-lg-1">
                    <label for="min_price" class="form-label">Min &#8377;</label>
                    <input type="number" name="min_price" id="min_price" class="form-control"
                           min="0" step="1" placeholder="0"
                           value="<?= htmlspecialchars($filters['min_price'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6 col-lg-1">
                    <label for="max_price" class="form-label">Max &#8377;</label>
                    <input type="number" name="max_price" id="max_price" class="form-control"
                           min="0" step="1" placeholder="Any"
                           value="<?= htmlspecialchars($filters['max_price'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6 col-lg-1">
                    <label for="sort" class="form-label">Sort</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="">Default</option>
                        <option value="price_asc" <?= ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>Price &uarr;</option>
                        <option value="price_desc" <?= ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Price &darr;</option>
                        <option value="type" <?= ($filters['sort'] ?? '') === 'type' ? 'selected' : '' ?>>Type</option>
                    </select>
                </div>
                <div class="col-lg-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="/rooms" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results Info -->
<?php if (!empty($filters['check_in']) && !empty($filters['check_out'])): ?>
    <div class="alert alert-light border mb-3">
        <i class="fas fa-calendar-alt"></i>
        Showing rooms available from
        <strong><?= htmlspecialchars($filters['check_in'], ENT_QUOTES, 'UTF-8') ?></strong>
        to
        <strong><?= htmlspecialchars($filters['check_out'], ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
<?php endif; ?>

<!-- Room Cards -->
<?php if (empty($rooms)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No rooms available for your selected criteria. Try adjusting your filters.
    </div>
<?php else: ?>
    <p class="text-muted mb-3"><?= count($rooms) ?> room<?= count($rooms) !== 1 ? 's' : '' ?> available</p>

    <div class="row">
        <?php foreach ($rooms as $room):
            $rType = $room['room_type'] ?? 'single';
            $badgeColor = $typeBadgeColors[$rType] ?? 'secondary';
            $gradient = $typeGradients[$rType] ?? $typeGradients['single'];
        ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <!-- Image placeholder -->
                    <div class="position-relative" style="background:<?= $gradient ?>;height:160px;border-radius:.375rem .375rem 0 0;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-bed fa-3x text-white opacity-50"></i>
                        <span class="position-absolute top-0 end-0 m-2 badge bg-<?= htmlspecialchars($badgeColor, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars(ucfirst($rType), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <!-- Room number & floor -->
                        <h5 class="card-title mb-1">Room <?= htmlspecialchars($room['room_number'], ENT_QUOTES, 'UTF-8') ?></h5>
                        <p class="text-muted small mb-2">Floor <?= (int)$room['floor_number'] ?></p>

                        <!-- Description (truncated) -->
                        <?php if (!empty($room['description'])): ?>
                            <p class="card-text small mb-2">
                                <?= htmlspecialchars(mb_strimwidth($room['description'], 0, 100, '...'), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        <?php endif; ?>

                        <!-- Key details -->
                        <div class="d-flex gap-3 mb-2 small">
                            <span><i class="fas fa-users text-muted"></i> <?= (int)$room['max_occupancy'] ?> guests</span>
                            <span><i class="fas fa-bed text-muted"></i> <?= (int)$room['num_beds'] ?> <?= htmlspecialchars($room['bed_type'] ?? 'bed', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <!-- Amenities -->
                        <?php
                            $amenities = json_decode($room['amenities'] ?? '[]', true);
                            if (is_array($amenities) && !empty($amenities)):
                        ?>
                            <div class="mb-2">
                                <?php foreach (array_slice($amenities, 0, 5) as $amenity): ?>
                                    <span class="badge bg-light text-dark border me-1 mb-1" style="font-size:.7rem;"><?= htmlspecialchars($amenity, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                                <?php if (count($amenities) > 5): ?>
                                    <span class="badge bg-light text-muted border mb-1" style="font-size:.7rem;">+<?= count($amenities) - 5 ?> more</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Price -->
                        <div class="mt-auto">
                            <span class="h4 text-primary">&#8377;<?= number_format((float)$room['base_price']) ?></span>
                            <small class="text-muted">/ night</small>
                            <?php if (!empty($room['weekend_price']) && (float)$room['weekend_price'] !== (float)$room['base_price']): ?>
                                <br><small class="text-muted">Weekend: &#8377;<?= number_format((float)$room['weekend_price']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-footer bg-white d-flex justify-content-between">
                        <a href="/room/<?= (int)$room['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <?php if ($user): ?>
                            <a href="/book/<?= (int)$room['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-calendar-check"></i> Book Now
                            </a>
                        <?php else: ?>
                            <a href="/login?redirect=<?= urlencode('/book/' . (int)$room['id']) ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-sign-in-alt"></i> Login to Book
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
