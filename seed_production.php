<?php
/**
 * SEED PRODUCTION DATA - EXTENDED & ENRICHED
 * Tento skript vytvoří komplexní ukázková data v databázi.
 * Účet "lucie" je vytvořen jako plnohodnotné portfolio s mnoha daty.
 * POZOR: Skript nejprve smaže (TRUNCATE) stávající data v tabulkách!
 */

require_once 'components/connector.php';

// Zabezpečení
$auth_key = "seed123";
if ($_SERVER['HTTP_HOST'] !== 'localhost' && (!isset($_GET['key']) || $_GET['key'] !== $auth_key)) {
    die("❌ Přístup odepřen. Pro spuštění na serveru použij: seed_production.php?key=$auth_key");
}

echo "<h2>🌱 Inicializace komplexních ukázkových dat...</h2>";

$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$tables = ['comments', 'reviews', 'login_logs', 'bookings', 'offers', 'cloud_files', 'role_requests', 'follows', 'likes', 'posts', 'profile_blocks', 'users'];
foreach ($tables as $table) {
    if ($conn->query("TRUNCATE TABLE `$table`")) {
        echo "✔ Tabulka `$table` promazána.<br>";
    }
}
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

function deleteDir($dirPath) {
    if (!is_dir($dirPath)) return;
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) deleteDir($file);
        else @unlink($file);
    }
    @rmdir($dirPath);
}

if ($_SERVER['HTTP_HOST'] === 'localhost') {
    $dirs_to_clear = ['cloud/', 'posts/', 'uploads/bookings/'];
    foreach ($dirs_to_clear as $d) {
        $files = glob($d . '{,.}[!.,!..]*', GLOB_BRACE);
        if ($files) {
            foreach ($files as $file) {
                if (is_dir($file)) deleteDir($file);
                else @unlink($file);
            }
        }
    }
}

echo "<h3>👥 Vytváření uživatelů...</h3>";
$users_to_create = [
    ['admin', 'System Administrator', 'admin@photoahh.cz', 'admin', 'admin', 'Hlavní správce systému Photo Ahh.'],
    ['fotograf', 'Tomáš Blesk', 'fotograf@photoahh.cz', '12345', 'author', 'Profesionální fotograf se zaměřením na portréty a krajinu.'],
    ['klient', 'Jan Novák', 'klient@photoahh.cz', '12345', 'user', 'Milovník kvalitní fotografie.'],
    ['lucie', 'Lucie Černá', 'lucie@photoahh.cz', '12345', 'author', 'Specialistka na svatební, rodinnou a portrétní fotografii. Mým cílem je zachytit jedinečné emoce a vytvořit vzpomínky na celý život. Focení je moje vášeň i profese.'],
    ['petr', 'Petr Svoboda', 'petr@photoahh.cz', '12345', 'user', 'Amatérský model a cestovatel.'],
    ['jana', 'Jana Veselá', 'jana@photoahh.cz', '12345', 'user', 'Maminka na plný úvazek, hledám fotografa pro rodinu.'],
    ['karel', 'Karel Dvořák', 'karel@photoahh.cz', '12345', 'user', 'Svatební koordinátor.'],
    ['eva', 'Eva Krásná', 'eva@photoahh.cz', '12345', 'user', 'Nadšená fanynka.'],
];

$user_ids = [];
foreach ($users_to_create as $u) {
    $hashed_pass = password_hash($u[3], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, role, bio) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $u[0], $u[1], $u[2], $hashed_pass, $u[4], $u[5]);
    $stmt->execute();
    $uid = $stmt->insert_id;
    $user_ids[$u[0]] = $uid;
}

// Sledování (Všichni sledují Lucii)
$f_lucie = $user_ids['lucie'];
foreach (['klient', 'petr', 'jana', 'karel', 'eva', 'fotograf'] as $follower) {
    $conn->query("INSERT INTO follows (follower_id, followed_id) VALUES ({$user_ids[$follower]}, $f_lucie)");
}
$conn->query("INSERT INTO follows (follower_id, followed_id) VALUES ({$user_ids['klient']}, {$user_ids['fotograf']})");


echo "<h3>📸 Vytváření příspěvků v Galerii...</h3>";
$posts_data = [
    // Fotograf
    ['fotograf', 'Zlatá hodinka', 'Nádherné světlo v centru Prahy.', 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800'],
    ['fotograf', 'Ranní mlha', 'Podzimní atmosféra v lese.', 'https://images.unsplash.com/photo-1447752875215-b2761acb3c5d?w=800'],
    // Lucie (Mnoho postů pro bohatou galerii)
    ['lucie', 'Podzimní portrét', 'Focení v přírodě.', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=800'],
    ['lucie', 'Svatba pod širým nebem', 'Dojemný okamžik u oltáře.', 'https://images.unsplash.com/photo-1519741497674-611481863552?w=800'],
    ['lucie', 'Dětský smích', 'Rodinné focení plné energie.', 'https://images.unsplash.com/photo-1511895426328-dc8714191300?w=800'],
    ['lucie', 'Přípravy nevěsty', 'Ranní nervozita a radost.', 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?w=800'],
    ['lucie', 'Párové focení v Paříži', 'Zahraniční projekt.', 'https://images.unsplash.com/photo-1522673607200-164d1b6ce486?w=800'],
    ['lucie', 'Detail prstýnků', 'Makro záběr ze včerejší svatby.', 'https://images.unsplash.com/photo-1515934751635-c81c6bc9a2d8?w=800'],
    ['lucie', 'Západ slunce', 'Krásný závěr dne.', 'https://images.unsplash.com/photo-1478147427282-58a87a120781?w=800'],
    ['lucie', 'Ateliér', 'Fashion editorial.', 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=800'],
];

$post_ids = [];
foreach ($posts_data as $p) {
    $uid = $user_ids[$p[0]];
    $urls = json_encode([$p[3]]);
    $meta = json_encode([['exif' => ['make' => 'Sony', 'model' => 'A7RIV', 'exposure' => '1/500', 'aperture' => 'f/1.4', 'iso' => '100']]]);
    $stmt = $conn->prepare("INSERT INTO posts (user_id, title, description, file_path, urls, metadata) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $uid, $p[1], $p[2], $p[3], $urls, $meta);
    $stmt->execute();
    $post_ids[] = $stmt->insert_id;
}

// Lajky pro Lucii
for ($i=2; $i<10; $i++) {
    $conn->query("INSERT INTO likes (user_id, post_id) VALUES ({$user_ids['klient']}, {$post_ids[$i]})");
    $conn->query("INSERT INTO likes (user_id, post_id) VALUES ({$user_ids['eva']}, {$post_ids[$i]})");
}

echo "<h3>💰 Vytváření ceníků...</h3>";
$offers_data = [
    ['fotograf', 'Portrétní focení', '60 minut focení, 10 upravených fotek.', 1500],
    ['lucie', 'Svatební balíček MINI', '4 hodiny focení (obřad + párové focení). Dodání 150 fotek.', 8000],
    ['lucie', 'Svatební balíček STANDARD', '8 hodin focení. Dodání 350 fotek. Předsvatební schůzka.', 15000],
    ['lucie', 'Svatební balíček MAXI', 'Celodenní focení od příprav po párty. Fotokoutek zdarma. 600+ fotek.', 25000],
    ['lucie', 'Rodinné focení', 'Pohodové odpoledne plné smíchu (lokalita dle domluvy).', 2500],
    ['lucie', 'Párové / Rande focení', 'Láska před objektivem.', 2000],
    ['lucie', 'Těhotenské focení', 'Jemné a emotivní zachycení očekávání.', 2200]
];

$offer_ids = [];
foreach ($offers_data as $o) {
    $aid = $user_ids[$o[0]];
    $stmt = $conn->prepare("INSERT INTO offers (author_id, title, description, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issd", $aid, $o[1], $o[2], $o[3]);
    $stmt->execute();
    $offer_ids[$o[1]] = $stmt->insert_id;
}


echo "<h3>🧩 Vytváření dynamických profilů (Mozaika & Bloky)...</h3>";

// LUCIE - Komplexní mozaika (12 polí naplno využitých)
$lucie_mosaic = [
    ['url' => 'https://images.unsplash.com/photo-1519741497674-611481863552?w=800', 'row' => 1, 'col' => 1, 'row_span' => 3, 'col_span' => 2], // Velká svatba vlevo
    ['url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=800', 'row' => 1, 'col' => 3, 'row_span' => 2, 'col_span' => 1], // Portrét nahoře uprostřed
    ['url' => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?w=800', 'row' => 1, 'col' => 4, 'row_span' => 2, 'col_span' => 1], // Děti nahoře vpravo
    ['url' => 'https://images.unsplash.com/photo-1515934751635-c81c6bc9a2d8?w=800', 'row' => 3, 'col' => 3, 'row_span' => 1, 'col_span' => 2], // Prstýnky dole vpravo široké
];

$blocks_lucie = [
    ['bio', json_encode(['title' => 'Ahoj, já jsem Lucie!', 'text' => 'Vítejte v mém světě. Specializuji se na přirozené světlo a autentické momenty. Svatby, rodiny a láska – to je to, co mě baví nejvíce. Každé focení je pro mě příběh, který chci vyprávět co nejlépe.']), 1],
    ['gallery', json_encode(['title' => 'Výběr z tvorby', 'block_height' => '700px', 'items' => $lucie_mosaic]), 2],
    ['offers', json_encode(['title' => 'Do čeho se můžeme pustit?', 'layout' => 'cards', 'selected_offers' => []]), 3], // prázdné = vše
    ['reviews', json_encode(['min_rating' => 1]), 4],
    ['contact', json_encode(['show_email' => true, 'title' => 'Napište mi, budu se těšit']), 5]
];

foreach ($blocks_lucie as $b) {
    $stmt = $conn->prepare("INSERT INTO profile_blocks (user_id, type, content, position) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $f_lucie, $b[0], $b[1], $b[2]);
    $stmt->execute();
}

// FOTOGRAF - Jednodušší
$fotograf_mosaic = [
    ['url' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800', 'row' => 1, 'col' => 1, 'row_span' => 3, 'col_span' => 4] // Jedna obří fotka
];
$blocks_fotograf = [
    ['bio', json_encode(['title' => 'O mně', 'text' => 'Jsem Tomáš a fotím už 10 let.']), 1],
    ['gallery', json_encode(['title' => 'Portfolio', 'block_height' => '400px', 'items' => $fotograf_mosaic]), 2],
    ['offers', json_encode(['title' => 'Ceník', 'layout' => 'cards', 'selected_offers' => []]), 3]
];
foreach ($blocks_fotograf as $b) {
    $stmt = $conn->prepare("INSERT INTO profile_blocks (user_id, type, content, position) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $user_ids['fotograf'], $b[0], $b[1], $b[2]);
    $stmt->execute();
}


echo "<h3>📅 Vytváření masivních objednávek a recenzí pro Lucii...</h3>";

$bookings_data = [
    // 1. Nová (odeslána) od hosta (Neregistrovaný uživatel)
    [$offer_ids['Rodinné focení'], null, $f_lucie, 'Rodina', date('Y-m-d', strtotime('+5 days')), 'Stromovka', '3000', 'Dobrý den, chtěli bychom nafotit naši malou.', 'odeslána', 0, ''],
    
    // 2. Potvrzená (čeká na focení)
    [$offer_ids['Svatební balíček MAXI'], $user_ids['jana'], $f_lucie, 'Svatba', date('Y-m-d', strtotime('+40 days')), 'Zámek', '25000', 'Moc se těšíme! Barvy svatby budou do zelena.', 'potvrzena', 0, ''],
    
    // 3. Protinabídka od fotografa
    [$offer_ids['Svatební balíček MINI'], $user_ids['petr'], $f_lucie, 'Malá svatba', date('Y-m-d', strtotime('+20 days')), 'Radnice', '8000', 'Chceme jen obřad.', 'protinabídka', 0, ''],
    
    // 4. Probíhající (Focení proběhlo, Lucie upravuje fotky)
    [$offer_ids['Párové / Rande focení'], $user_ids['klient'], $f_lucie, 'Rande', date('Y-m-d', strtotime('-2 days')), 'Centrum Prahy', '2000', 'Podzimní fotky v kabátech.', 'probíhající', 0, ''],
    
    // 5. Hotova (Nezaplacená) - klient vidí vodoznaky
    [$offer_ids['Těhotenské focení'], $user_ids['eva'], $f_lucie, 'Těhu', date('Y-m-d', strtotime('-15 days')), 'Ateliér', '2200', 'Představuji si černobílé fotky.', 'hotova', 0, '["https://images.unsplash.com/photo-1511285560929-80b456fea0bc?w=800"]'],
    
    // 6. Zamítnuta s důvodem (uživatel chtěl nesmysl)
    [$offer_ids['Svatební balíček STANDARD'], $user_ids['karel'], $f_lucie, 'Svatba', date('Y-m-d', strtotime('+10 days')), 'Zahraničí', '15000', 'Chceme fotit v Itálii, cestu neplatíme.', 'zamítnuta_s_duvodem', 0, ''],
    
    // --- HISTORICKÉ HOTOVÉ A ZAPLACENÉ (Pro generování RECENTZÍ) ---
    [$offer_ids['Svatební balíček MAXI'], $user_ids['karel'], $f_lucie, 'Svatba', date('Y-m-d', strtotime('-60 days')), 'Statek', '25000', 'Loňská svatba.', 'hotova', 1, '["https://images.unsplash.com/photo-1519741497674-611481863552?w=800"]'],
    [$offer_ids['Rodinné focení'], $user_ids['jana'], $f_lucie, 'Rodina', date('Y-m-d', strtotime('-100 days')), 'Park', '2500', 'Jarní rodinné focení.', 'hotova', 1, '["https://images.unsplash.com/photo-1511895426328-dc8714191300?w=800"]'],
    [$offer_ids['Párové / Rande focení'], $user_ids['petr'], $f_lucie, 'Párové', date('Y-m-d', strtotime('-120 days')), 'Les', '2000', 'Tajné zasnoubení.', 'hotova', 1, '["https://images.unsplash.com/photo-1522673607200-164d1b6ce486?w=800"]'],
    [$offer_ids['Těhotenské focení'], $user_ids['klient'], $f_lucie, 'Těhu', date('Y-m-d', strtotime('-200 days')), 'Ateliér', '2200', 'První focení.', 'hotova', 1, '["https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=800"]'],
];

$reviews_to_create = [
    [6, $user_ids['karel'], 5, 'Lucie byla naprosto neuvěřitelná! Její přístup na naší svatbě byl natolik profesionální a nenápadný, že jsme o ní skoro nevěděli, ale přitom zachytila každý důležitý moment. Fotky nás rozplakaly dojetím.'],
    [7, $user_ids['jana'], 5, 'Moc milá fotografka, která to s dětmi neskutečně umí. Náš malý neposeda se normálně fotit nechce, ale s Lucií to byla spíš hra a fotky jsou prostě nádherné!'],
    [8, $user_ids['petr'], 4, 'Byli jsme lehce nervózní, protože se neradi fotíme, ale Lucie nás okamžitě uvolnila. Výsledek je skvělý, jen jsme na fotky čekali o 2 dny déle, než bylo v plánu, ale vyplatilo se to.'],
    [9, $user_ids['klient'], 5, 'Nádherné, jemné a velmi vkusné těhotenské fotografie. Cítila jsem se v ateliéru velmi příjemně a bezpečně. Rozhodně Lucii doporučuji všem budoucím maminkám.']
];

foreach ($bookings_data as $i => $b) {
    $stmt = $conn->prepare("INSERT INTO bookings (offer_id, user_id, photographer_id, type, date, location, budget, description, status, is_paid, completed_photos_json, rejection_reason, counter_offer_note, guest_name, guest_email, guest_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $hash = ($b[8] === 'hotova' || $b[1] === null) ? bin2hex(random_bytes(16)) : null;
    $rej_reason = ($b[8] === 'zamítnuta_s_duvodem') ? 'Omlouvám se, ale tento požadavek není reálný.' : null;
    $counter_note = ($b[8] === 'protinabídka') ? 'Za tuto cenu to bohužel nepůjde, nabízím standardních 10 000 Kč.' : null;
    
    $guest_name = ($b[1] === null) ? 'Anonymní Host' : null;
    $guest_email = ($b[1] === null) ? 'host@example.com' : null;
    $guest_phone = ($b[1] === null) ? '+420 123 456 789' : null;
    $stmt->bind_param("iiissssssissssss", $b[0], $b[1], $b[2], $b[3], $b[4], $b[5], $b[6], $b[7], $b[8], $b[9], $b[10], $rej_reason, $counter_note, $guest_name, $guest_email, $guest_phone);
    $stmt->execute();
    $bid = $stmt->insert_id;

    if ($hash) {
        $conn->query("UPDATE bookings SET access_hash = '$hash' WHERE id = $bid");
    }
    }
// Vložení recenzí pro Lucii
foreach ($reviews_to_create as $r) {
    // Booking IDs pro historické zakázky začínají od indexu 6 + 1 (protože auto-increment) = 7, 8, 9, 10
    $bid = $r[0] + 1; 
    $stmt = $conn->prepare("INSERT INTO reviews (booking_id, photographer_id, user_id, rating, comment, approved) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("iiiis", $bid, $f_lucie, $r[1], $r[3], $r[4]);
    $stmt->execute();
}

// Přidám jednu zakázku a recenzi pro druhého fotografa, aby neměl nulu
$conn->query("INSERT INTO bookings (offer_id, user_id, photographer_id, type, date, status, is_paid, access_hash) VALUES (1, {$user_ids['klient']}, {$user_ids['fotograf']}, 'Portrét', '2025-01-01', 'hotova', 1, 'hash123')");
$conn->query("INSERT INTO reviews (booking_id, photographer_id, user_id, rating, comment, approved) VALUES (11, {$user_ids['fotograf']}, {$user_ids['klient']}, 5, 'Super focení, Tomáš je profík!', 1)");


echo "<h3>✅ Hotovo!</h3>";
echo "<div style='background: #111; color: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--accent); margin-top: 20px;'>";
echo "<h2 style='color: var(--accent); margin-top: 0;'>Komplexní data nahrána</h2>";
echo "<p>Účet <b>lucie</b> (heslo: 12345) je nyní plně vybaven pro prezentaci. Po přihlášení najdeš na jejím profilu a dashboardu:</p>";
echo "<ul style='line-height: 1.8;'>";
echo "<li><b>Plný Dashboard:</b> Ukazuje statistiky, příjmy (z 4 zaplacených zakázek) a skvělé hodnocení (4.75 z 4 recenzí).</li>";
echo "<li><b>Živý Profil:</b> Složený z detailního Bia, parádní 4x3 mozaiky portfolia, širokého ceníku a vřelé sekce recenzí.</li>";
echo "<li><b>Rušný kalendář zakázek:</b> K vyzkoušení je tam 10 různých rezervací ve všech možných stavech:</li>";
echo "<ul>";
echo "<li>Nová žádost od anonymního hosta.</li>";
echo "<li>Čekající (potvrzená) velká svatba.</li>";
echo "<li>Rozpracované (probíhající) focení v Praze.</li>";
echo "<li>Čekání na platbu (hotová, neplacená).</li>";
echo "<li>Otevřená protinabídka s klientem.</li>";
echo "<li>Zamítnutá nesmyslná žádost.</li>";
echo "</ul>";
echo "</ul>";
echo "</div>";

echo "<p><br><a href='index.php' style='padding: 15px 30px; background: var(--accent); color: #000; text-decoration: none; font-weight: 800; border-radius: 8px;'>Zobrazit výsledek na Hlavní stránce</a></p>";

$conn->close();
?>