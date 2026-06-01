<?php
// Contact Block
$show_email = $data['show_email'] ?? true;
$title = $data['title'] ?? 'Kontaktujte mě';

// Get contact details from DB
$stmt = $conn->prepare("SELECT email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$contact_info = $stmt->get_result()->fetch_assoc();
?>
<section class="profile-section block-contact" style="margin-top: 60px; padding: 40px; background: var(--bg2); border-radius: 24px; border: 1px solid var(--border);">
    <h2 style="font-size: 24px; font-weight: 800; color: var(--text-h); margin-bottom: 25px;"><?php echo htmlspecialchars($title); ?></h2>
    <div style="display: flex; flex-wrap: wrap; gap: 30px;">
        <?php if ($show_email && isset($contact_info['email']) && $contact_info['email']): ?>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: var(--bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--accent);">
                    <i data-lucide="mail"></i>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 800; opacity: 0.5; text-transform: uppercase;">E-mail</div>
                    <a href="mailto:<?php echo htmlspecialchars($contact_info['email']); ?>" style="font-weight: 700; color: var(--text-h);"><?php echo htmlspecialchars($contact_info['email']); ?></a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($contact_info['phone']) && $contact_info['phone']): ?>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: var(--bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--accent);">
                    <i data-lucide="phone"></i>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 800; opacity: 0.5; text-transform: uppercase;">Telefon</div>
                    <a href="tel:<?php echo htmlspecialchars($contact_info['phone']); ?>" style="font-weight: 700; color: var(--text-h);"><?php echo htmlspecialchars($contact_info['phone']); ?></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
