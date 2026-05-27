<?php
$custom_title = "Admin Panel - Photo Ahh";
$extra_css = "admin.css";
include 'components/header.php';

if (!$is_admin) { header("Location: index.php"); exit(); }

// Zpracování akcí
if (isset($_GET['approve'])) {
    $req_id = $_GET['approve'];
    $stmt = $conn->prepare("SELECT user_id FROM role_requests WHERE id = ?");
    $stmt->bind_param("i", $req_id);
    $stmt->execute();
    if ($req = $stmt->get_result()->fetch_assoc()) {
        $uid = $req['user_id'];
        $conn->query("UPDATE users SET role = 'author' WHERE id = $uid");
        $conn->query("UPDATE role_requests SET status = 'approved' WHERE id = $req_id");
    }
    header("Location: admin.php"); exit();
}

if (isset($_GET['reject'])) {
    $req_id = $_GET['reject'];
    $conn->query("UPDATE role_requests SET status = 'rejected' WHERE id = $req_id");
    header("Location: admin.php"); exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id = $id");
    }
    header("Location: admin.php"); exit();
}

if (isset($_POST['update_limit'])) {
    $uid = $_POST['user_id'];
    $new_limit = $_POST['new_limit'] * 1024 * 1024;
    $stmt = $conn->prepare("UPDATE users SET storage_limit = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_limit, $uid);
    $stmt->execute();
    header("Location: admin.php"); exit();
}

if (isset($_POST['admin_change_password'])) {
    $uid = $_POST['user_id'];
    $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $new_pass, $uid);
    $stmt->execute();
    header("Location: admin.php?msg=pass_updated"); exit();
}

$users = $conn->query("SELECT * FROM users");
$logs = $conn->query("SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 20");
$requests = $conn->query("SELECT rr.*, u.username, u.full_name FROM role_requests rr JOIN users u ON rr.user_id = u.id WHERE rr.status = 'pending'");
$cloud_usage = $conn->query("
    SELECT u.id, u.username, u.role, u.storage_limit, 
    (SELECT SUM(file_size) FROM cloud_files WHERE user_id = u.id) as used_bytes
    FROM users u
");
?>

<main class="admin-container">
    <div class="admin-section">
        <h2>Admin Panel</h2>
        <p style="color: var(--text); opacity: 0.7; margin-bottom: 30px;">Správa platformy a uživatelů.</p>

        <section style="margin-bottom: 50px;">
            <h3><i data-lucide="cloud"></i> Správa místa (Cloud)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Uživatel</th>
                        <th>Role</th>
                        <th>Využito</th>
                        <th>Limit (MB)</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($cu = $cloud_usage->fetch_assoc()): 
                        $limit_mb = round($cu['storage_limit'] / (1024*1024), 0);
                        $used_mb = round($cu['used_bytes'] / (1024*1024), 2);
                        $free_mb = round($limit_mb - $used_mb, 2);
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($cu['username']); ?></strong></td>
                        <td><span class="badge"><?php echo htmlspecialchars($cu['role']); ?></span></td>
                        <td><?php echo $used_mb; ?> MB</td>
                        <td>
                            <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                                <input type="hidden" name="user_id" value="<?php echo $cu['id']; ?>">
                                <input type="number" name="new_limit" value="<?php echo $limit_mb; ?>" class="admin-input-small">
                                <button type="submit" name="update_limit" class="btn-admin btn-admin-primary">Uložit</button>
                            </form>
                        </td>
                        <td><a href="cloud.php?user_view=<?php echo $cu['id']; ?>" class="btn-admin btn-admin-secondary">Soubory</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <section style="margin-bottom: 50px;">
            <h3><i data-lucide="user-check"></i> Žádosti o roli fotografa</h3>
            <?php if($requests->num_rows > 0): ?>
                <table>
                    <thead><tr><th>Uživatel</th><th>Jméno</th><th>Datum</th><th>Akce</th></tr></thead>
                    <tbody>
                        <?php while($r = $requests->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                            <td><?php echo date('d. m. Y', strtotime($r['request_date'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 10px;">
                                    <a href="admin.php?approve=<?php echo $r['id']; ?>" class="btn-admin btn-admin-success">Schválit</a>
                                    <a href="admin.php?reject=<?php echo $r['id']; ?>" class="btn-admin btn-admin-danger">Zamítnout</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: var(--text); opacity: 0.5; font-style: italic; padding: 20px 0;">Žádné nevyřízené žádosti.</p>
            <?php endif; ?>
        </section>

        <section style="margin-bottom: 50px;">
            <h3><i data-lucide="users"></i> Správa uživatelů</h3>
            <table>
                <thead><tr><th>ID</th><th>Jméno</th><th>Role</th><th>Akce</th></tr></thead>
                <tbody>
                    <?php while($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                        <td><span class="badge"><?php echo htmlspecialchars($u['role']); ?></span></td>
                        <td>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <button onclick="openPasswordModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')" class="btn-admin btn-admin-secondary">Změnit heslo</button>
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="admin.php?delete=<?php echo $u['id']; ?>" class="btn-admin btn-admin-danger" onclick="return confirm('Smazat uživatele?')">Smazat</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h3><i data-lucide="activity"></i> Logy přihlášení</h3>
            <table style="font-size: 13px;">
                <thead><tr><th>Uživatel</th><th>Čas</th><th>Stav</th><th>IP</th></tr></thead>
                <tbody>
                    <?php while($l = $logs->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($l['username']); ?></td>
                        <td><?php echo date('d. m. Y H:i', strtotime($l['login_time'])); ?></td>
                        <td>
                            <?php if ($l['status'] == 'Success'): ?>
                                <span style="color: #2ecc71; font-weight: 700;">Úspěch</span>
                            <?php else: ?>
                                <span style="color: #ef4444; font-weight: 700;">Selhání</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo htmlspecialchars($l['ip_address']); ?></code></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </div>
</main>

<!-- Password Modal -->
<div id="passwordModal" class="modal">
    <div class="modal-content" style="background: var(--bg2); border: 1px solid var(--border); border-radius: 24px; box-shadow: var(--shadow);">
        <h3 style="margin-bottom: 25px; border: none; font-size: 22px;">Změnit heslo: <span id="modalUsername" style="color: var(--accent);"></span></h3>
        <form method="POST">
            <input type="hidden" name="user_id" id="modalUserId">
            <div style="margin-bottom: 20px;">
                <label>NOVÉ HESLO</label>
                <input type="password" name="new_password" required placeholder="Zadejte nové heslo">
            </div>
            <div style="display: flex; gap: 12px; margin-top: 30px;">
                <button type="button" onclick="closePasswordModal()" class="btn-admin btn-admin-secondary" style="flex: 1; height: 45px;">Zrušit</button>
                <button type="submit" name="admin_change_password" class="btn-admin btn-admin-primary" style="flex: 1; height: 45px;">Uložit změny</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPasswordModal(id, username) {
    document.getElementById('modalUserId').value = id;
    document.getElementById('modalUsername').innerText = username;
    document.getElementById('passwordModal').style.setProperty('display', 'flex', 'important');
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.setProperty('display', 'none', 'important');
}

window.onclick = function(event) {
    let modal = document.getElementById('passwordModal');
    if (event.target == modal) {
        closePasswordModal();
    }
}
</script>

<?php include 'components/footer.php'; ?>
