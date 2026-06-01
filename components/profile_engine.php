<?php

/**
 * Renders a specific profile block based on its type and data.
 */
function renderProfileBlock($block, $conn, $is_owner) {
    global $view_user_id, $my_id, $my_role;
    $data = json_decode($block['content'], true);
    $type = $block['type'];
    
    echo "<div class='profile-block-wrapper' data-block-id='{$block['id']}'>";
    
    switch ($type) {
        case 'bio':
            include __DIR__ . '/profile_blocks/bio.php';
            break;
        case 'gallery':
            include __DIR__ . '/profile_blocks/gallery.php';
            break;
        case 'offers':
            include __DIR__ . '/profile_blocks/offers.php';
            break;
        case 'reviews':
            include __DIR__ . '/profile_blocks/reviews.php';
            break;
        case 'contact':
            include __DIR__ . '/profile_blocks/contact.php';
            break;
    }
    
    echo "</div>";
}
?>
