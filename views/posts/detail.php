<?php
require_once PATH_MODEL . 'PostModel.php';
?>

<div class="post-detail-page">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-nav" aria-label="breadcrumb">
            <ol>
                <li><a href="<?= BASE_URL ?>">Trang chủ</a></li>
                <li class="separator">/</li>
                <li><a href="<?= BASE_URL ?>?action=posts">Tin tức</a></li>
                <li class="separator">/</li>
                <li class="current"><?= htmlspecialchars(mb_substr($post['title'], 0, 50)) ?><?= mb_strlen($post['title']) > 50 ? '...' : '' ?></li>
            </ol>
        </nav>

        <!-- Hero Image with Title -->
        <?php if (!empty($post['thumbnail'])): ?>
            <div class="post-hero">
                <img src="<?= htmlspecialchars(getProductImageUrl($post['thumbnail'])) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="post-hero-image">
                <div class="post-hero-overlay">
                    <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>
                    <div class="post-meta">
                        <div class="post-meta-item">
                            <i class="bi bi-calendar3"></i>
                            <span><?= date('d/m/Y', strtotime($post['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="post-wrapper">
                <h1 class="post-title plain"><?= htmlspecialchars($post['title']) ?></h1>
                <div class="post-meta plain">
                    <div class="post-meta-item">
                        <i class="bi bi-calendar3"></i>
                        <span><?= date('d/m/Y', strtotime($post['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="post-wrapper">
            <!-- Excerpt -->
            <?php if (!empty($post['excerpt'])): ?>
                <div class="post-excerpt">
                    <?= htmlspecialchars($post['excerpt']) ?>
                </div>
            <?php endif; ?>
            
            <!-- Content -->
            <div class="post-content">
                <?php 
                $content = $post['content'] ?? '';
                // Kiểm tra nếu content có HTML tags
                if (strip_tags($content) !== $content) {
                    echo $content;
                } else {
                    echo nl2br(htmlspecialchars($content));
                }
                ?>
            </div>

            <!-- Actions -->
            <div class="post-actions">
                <a href="<?= BASE_URL ?>?action=posts" class="back-button">
                    <i class="bi bi-arrow-left"></i>
                    Quay lại danh sách
                </a>
                
                <div class="social-share">
                    <span class="social-share-label">Chia sẻ:</span>
                    <div class="social-share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(BASE_URL . '?action=post-detail&id=' . $post['post_id']) ?>" 
                           target="_blank" 
                           class="social-btn facebook"
                           title="Chia sẻ lên Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode(BASE_URL . '?action=post-detail&id=' . $post['post_id']) ?>&text=<?= urlencode($post['title']) ?>" 
                           target="_blank" 
                           class="social-btn twitter"
                           title="Chia sẻ lên Twitter">
                            <i class="bi bi-twitter"></i>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(BASE_URL . '?action=post-detail&id=' . $post['post_id']) ?>" 
                           target="_blank" 
                           class="social-btn linkedin"
                           title="Chia sẻ lên LinkedIn">
                            <i class="bi bi-linkedin"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Posts -->
        <?php
        $postModel = new PostModel();
        // Lấy 4 bài, trừ bài hiện tại
        $allPosts = $postModel->getAll(null, 'published', 4);
        $relatedPosts = array_filter($allPosts, function($p) use ($post) {
            return $p['post_id'] != $post['post_id'];
        });
        $relatedPosts = array_slice($relatedPosts, 0, 3);
        ?>
        
        <?php if (!empty($relatedPosts)): ?>
            <div class="related-posts">
                <h2 class="related-posts-title">Bài viết liên quan</h2>
                <div class="row g-4">
                    <?php foreach ($relatedPosts as $relatedPost): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <article class="related-post-card">
                                <a href="<?= BASE_URL ?>?action=post-detail&id=<?= $relatedPost['post_id'] ?>">
                                    <?php 
                                        $imgSrc = !empty($relatedPost['thumbnail']) ? getProductImageUrl($relatedPost['thumbnail']) : BASE_URL . 'assets/images/default.jpg';
                                    ?>
                                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($relatedPost['title']) ?>">
                                </a>
                                <div class="related-post-card-body">
                                    <p class="related-post-card-date">
                                        <i class="bi bi-calendar3 me-2"></i><?= date('d/m/Y', strtotime($relatedPost['created_at'])) ?>
                                    </p>
                                    <h3 class="related-post-card-title">
                                        <a href="<?= BASE_URL ?>?action=post-detail&id=<?= $relatedPost['post_id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($relatedPost['title']) ?>
                                        </a>
                                    </h3>
                                    <a href="<?= BASE_URL ?>?action=post-detail&id=<?= $relatedPost['post_id'] ?>" class="related-post-card-link">
                                        Đọc thêm <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

