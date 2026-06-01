<?php
// Bio Block
$title = $data['title'] ?? 'O mně';
$text = $data['text'] ?? '';
$parsedown = new Parsedown();
?>
<section class="profile-section block-bio">
    <h2 style="font-size: 24px; font-weight: 800; color: var(--text-h); margin-bottom: 20px;"><?php echo htmlspecialchars($title); ?></h2>
    <div class="bio-content" style="line-height: 1.8; opacity: 0.8;">
        <?php echo $parsedown->text($text); ?>
    </div>
</section>
