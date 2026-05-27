<!-- Global Post Detail Modal -->
<div id="postDetailModal" class="modal-overlay" onclick="if(event.target === this) closePostModal()">
    <div id="postDetailContent" class="modal-container">
        <!-- Content will be loaded here via AJAX from get_post_detail.php -->
    </div>
</div>

<script>
function openPostModal(postId) {
    const modal = document.getElementById('postDetailModal');
    const content = document.getElementById('postDetailContent');
    
    modal.style.display = 'flex';
    content.innerHTML = '<div style="padding: 100px; text-align: center; width: 100%; color: var(--text); font-weight: bold;">Načítám...</div>';
    
    // Reset index for the new post
    if (typeof modalSlideIndex !== 'undefined') modalSlideIndex = 0;

    fetch('get_post_detail.php?id=' + postId)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
}

function closePostModal() {
    document.getElementById('postDetailModal').style.display = 'none';
}

// These functions handle the carousel inside the modal
let modalSlideIndex = 0;
function moveSlideModal(direction) {
    const slides = document.getElementById('slides-modal');
    if (!slides) return;
    const totalSlides = slides.children.length;
    
    modalSlideIndex += direction;
    if (modalSlideIndex >= totalSlides) modalSlideIndex = 0;
    if (modalSlideIndex < 0) modalSlideIndex = totalSlides - 1;
    
    slides.style.transform = `translateX(-${modalSlideIndex * 100}%)`;
}

function toggleLikeModal(postId) {
    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('toggle_like.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const icon = document.getElementById('like-icon-modal');
        const count = document.getElementById('like-count-modal');
        
        if (data.status === 'liked') {
            icon.style.color = '#ff4444';
            icon.style.fill = '#ff4444';
        } else {
            icon.style.color = 'var(--text-h)';
            icon.style.fill = 'none';
        }
        count.innerText = data.likes;

        // Sync with the card on the page if it exists (Feed or Profile)
        const feedIcon = document.getElementById('like-icon-' + postId);
        const feedCount = document.getElementById('like-count-' + postId);
        if (feedIcon) {
            if (data.status === 'liked') {
                feedIcon.classList.add('active');
            } else {
                feedIcon.classList.remove('active');
            }
            feedCount.innerText = data.likes;
        }
    });
}
</script>