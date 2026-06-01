<?php
$custom_title = "Feed - Photo Ahh";
$extra_css = "index.css";
include 'components/header.php';


$my_user_id = $_SESSION['user_id'] ?? 0;
$stmt = $conn->prepare("SELECT p.*, u.username as author_name, u.avatar as author_avatar,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
        FROM `posts` p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC");
$stmt->bind_param("i", $my_user_id);
$stmt->execute();
$result = $stmt->get_result();
$posts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}
?>

<main class="feed-container">
    <div class="content-inner">
        <div class="feed-content-wrapper">
        <?php if (!empty($posts)): ?>
            <?php foreach ($posts as $post): 
                $images = json_decode($post['urls'] ?? '[]');
                if (!$images && isset($post['file_path'])) $images = [$post['file_path']];
                elseif (!$images) $images = [];
                
                $post_id = $post['id'];
            ?>
                <article class="photo-card" id="card-<?php echo htmlspecialchars($post_id); ?>">
                    
                    <!-- Header with author -->
                    <div class="photo-card-header">
                        <a href="profile.php?id=<?php echo htmlspecialchars($post['user_id']); ?>" class="author-link">
                            <img src="<?php echo htmlspecialchars($post['author_avatar']); ?>" class="author-avatar">
                            <span class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></span>
                        </a>
                    </div>

                    <!-- Image Carousel -->
                    <div class="carousel" onclick="openPostModal(<?php echo htmlspecialchars($post_id); ?>)">
                        <div class="slides" id="slides-<?php echo htmlspecialchars($post_id); ?>">
                            <?php foreach ($images as $img_url): ?>
                                <img src="<?php echo htmlspecialchars($img_url); ?>" class="slide-img">
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($images) > 1): ?>
                            <button class="prev" onclick="event.stopPropagation(); moveSlide(<?php echo htmlspecialchars($post_id); ?>, -1)">
                                <i data-lucide="chevron-left"></i>
                            </button>
                            <button class="next" onclick="event.stopPropagation(); moveSlide(<?php echo htmlspecialchars($post_id); ?>, 1)">
                                <i data-lucide="chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Post Content -->
                    <div class="photo-info">
                        <div class="interaction-row">
                            <div class="like-btn" onclick="toggleLike(<?php echo htmlspecialchars($post_id); ?>)">
                                <i id="like-icon-<?php echo htmlspecialchars($post_id); ?>" data-lucide="heart" 
                                   class="heart-icon <?php echo $post['is_liked'] ? 'active' : ''; ?>">
                                </i>
                                <span id="like-count-<?php echo htmlspecialchars($post_id); ?>" class="likes-count"><?php echo htmlspecialchars($post['likes_count']); ?></span>
                            </div>
                        </div>

                        <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <?php if (!empty($post['description'])): ?>
                            <p class="post-description"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                        <?php endif; ?>
                        <p class="post-date"><?php echo htmlspecialchars(date('j. n. Y', strtotime($post['created_at']))); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty-msg">Zatím tu nejsou žádné příspěvky.</p>
        <?php endif; ?>
        </div>
    </div>
</main>

<script>
const slideIndices = {};

function moveSlide(postId, direction) {
    if (!slideIndices[postId]) slideIndices[postId] = 0;
    
    const slides = document.getElementById('slides-' + postId);
    if (!slides) return;
    
    const totalSlides = slides.children.length;
    slideIndices[postId] += direction;
    
    if (slideIndices[postId] >= totalSlides) slideIndices[postId] = 0;
    if (slideIndices[postId] < 0) slideIndices[postId] = totalSlides - 1;
    
    slides.style.transform = `translateX(-${slideIndices[postId] * 100}%)`;
}

function toggleLike(postId) {
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch('toggle_like.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const icon = document.getElementById('like-icon-' + postId);
        const count = document.getElementById('like-count-' + postId);
        
        if (data.status === 'liked') {
            icon.classList.add('active');
        } else {
            icon.classList.remove('active');
        }
        count.innerText = data.likes;
    });
}
</script>

<?php include 'components/footer.php'; ?>
