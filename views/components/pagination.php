<?php
/**
 * Pagination component
 * Expects: $pagination array with keys: current_page, total_pages, total, has_prev, has_next
 * Optional: $base_url (defaults to current path), $query_params (extra GET params to preserve)
 */
if (!isset($pagination) || $pagination['total_pages'] <= 1) return;

$currentPage = $pagination['current_page'];
$totalPages = $pagination['total_pages'];
$total = $pagination['total'];

// Build base URL preserving existing query params
$currentUri = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
$existingParams = $_GET ?? [];
unset($existingParams['page']); // Remove current page param
$queryString = http_build_query($existingParams);
$baseUrl = $currentUri . ($queryString ? '?' . $queryString . '&' : '?');
?>

<nav aria-label="Page navigation" class="mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Showing page <?= $currentPage ?> of <?= $totalPages ?> (<?= $total ?> total)
        </small>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= !$pagination['has_prev'] ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($baseUrl . 'page=' . ($currentPage - 1), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <?php
            // Show page numbers with ellipsis
            $start = max(1, $currentPage - 2);
            $end = min($totalPages, $currentPage + 2);
            if ($start > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($baseUrl . 'page=1', ENT_QUOTES, 'UTF-8') ?>">1</a></li>
                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <?php endif;
            for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($baseUrl . 'page=' . $i, ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($baseUrl . 'page=' . $totalPages, ENT_QUOTES, 'UTF-8') ?>"><?= $totalPages ?></a></li>
            <?php endif; ?>
            <li class="page-item <?= !$pagination['has_next'] ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($baseUrl . 'page=' . ($currentPage + 1), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </div>
</nav>
