<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold">Contact Us</h1>
        <p class="lead text-muted">We'd love to hear from you. Get in touch with Grand Plaza Hotel & Resort.</p>
    </div>

    <div class="row g-4">
        <!-- Hotel Info Card -->
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-hotel"></i> Hotel Information</h5>
                </div>
                <div class="card-body">
                    <h5><?= htmlspecialchars($hotel['name'] ?? 'Grand Plaza Hotel & Resort', ENT_QUOTES, 'UTF-8') ?></h5>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1"><i class="fas fa-map-marker-alt text-danger"></i> Address</h6>
                        <p class="mb-0">
                            <?= htmlspecialchars($hotel['address'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
                            <?= htmlspecialchars(($hotel['city'] ?? '') . ', ' . ($hotel['state'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($hotel['zip_code'])): ?>
                                <?= htmlspecialchars($hotel['zip_code'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1"><i class="fas fa-phone text-success"></i> Phone</h6>
                        <p class="mb-0"><?= htmlspecialchars($hotel['phone'] ?? '+91-22-6789-0100', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1"><i class="fas fa-envelope text-info"></i> Email</h6>
                        <p class="mb-0"><?= htmlspecialchars($hotel['email'] ?? 'reservations@grandplaza.in', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>

                    <hr>

                    <!-- Map Placeholder -->
                    <div class="bg-light border rounded text-center py-5">
                        <i class="fas fa-map-marked-alt fa-3x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Map view coming soon</p>
                        <small class="text-muted"><?= htmlspecialchars(($hotel['address'] ?? '') . ', ' . ($hotel['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-paper-plane"></i> Send Us a Message</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/contact">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       placeholder="Your full name" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       placeholder="your.email@example.com" maxlength="150">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone <span class="text-muted">(optional)</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       placeholder="+91-XXXXX-XXXXX" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                <select class="form-select" id="subject" name="subject" required>
                                    <option value="">Select a subject</option>
                                    <option value="General Inquiry">General Inquiry</option>
                                    <option value="Reservation">Reservation</option>
                                    <option value="Complaint">Complaint</option>
                                    <option value="Feedback">Feedback</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="5" required
                                      placeholder="How can we help you? (minimum 10 characters)" minlength="10" maxlength="2000"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
