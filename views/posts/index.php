<div class="container news-page">
    <div class="news-header">
        <h1 class="news-title">Tin Tức</h1>
        <p class="text-muted">Cập nhật những xu hướng thời trang mới nhất</p>
    </div>

    <?php if (empty($posts)): ?>
        <div class="text-center py-5">
            <i class="bi bi-newspaper empty-icon-lg"></i>
            <h3 class="mt-3">Chưa có tin tức nào</h3>
            <p class="text-muted">Vui lòng quay lại sau.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($posts as $post): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <article class="news-card">
                        <a href="<?= BASE_URL ?>?action=post-detail&id=<?= $post['post_id'] ?>">
                            <?php 
                                $imgSrc = !empty($post['thumbnail']) ? getProductImageUrl($post['thumbnail']) : BASE_URL . 'assets/images/default.jpg';
                            ?>
                            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                        </a>
                        <div class="news-card-body">
                            <p class="news-card-date">
                                <i class="bi bi-calendar3 me-2"></i><?= date('d/m/Y', strtotime($post['created_at'])) ?>
                            </p>
                            <h3 class="news-card-title">
                                <a href="<?= BASE_URL ?>?action=post-detail&id=<?= $post['post_id'] ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h3>
                            <p class="news-card-excerpt"><?= htmlspecialchars($post['excerpt'] ?? '') ?></p>
                            <a href="<?= BASE_URL ?>?action=post-detail&id=<?= $post['post_id'] ?>" class="news-card-link">
                                Đọc thêm <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav aria-label="Phân trang tin tức">
                <ul class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= BASE_URL ?>?action=posts&page=<?= $currentPage - 1 ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == 1 || $i == $totalPages || ($i >= $currentPage - 2 && $i <= $currentPage + 2)): ?>
                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="<?= BASE_URL ?>?action=posts&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php elseif ($i == $currentPage - 3 || $i == $currentPage + 3): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= BASE_URL ?>?action=posts&page=<?= $currentPage + 1 ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

