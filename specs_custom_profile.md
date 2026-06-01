# Specifikace: Blokově přizpůsobitelný profil

Tento dokument popisuje implementaci dynamického systému profilů, který fotografům umožní skládat svou prezentaci z jednotlivých modulů (bloků) v libovolném pořadí. Tato implementace naplňuje a rozšiřuje **Bod 5** hlavních specifikací.

## 1. Databázová struktura

Vznikne nová tabulka `profile_blocks`, která nahradí statické zobrazení profilu.

```sql
CREATE TABLE IF NOT EXISTS `profile_blocks` (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED NOT NULL,
    type ENUM('bio', 'gallery', 'offers', 'reviews', 'contact') NOT NULL,
    content TEXT, -- JSON formát pro specifická data bloku
    position INT(3) DEFAULT 0,
    is_visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 2. Podporované typy bloků

| Typ bloku | Popis | Obsah JSON (`content`) |
| :--- | :--- | :--- |
| `bio` | Textové představení autora (Markdown) | `{"title": "O mně", "text": "..."}` |
| `gallery` | Mřížka VYBRANÝCH fotografií (1-8 ks) | `{"images": ["url1", ...], "cols": 3, "width": "1200px"}` |
| `offers` | Výpis balíčků z ceníku | `{"layout": "cards"}` |
| `reviews` | Sekce s hodnocením od klientů | `{"min_rating": 4}` |
| `contact` | Kontaktní formulář nebo údaje | `{"show_email": true}` |

## 3. Logika fungování

### A. Rendering (Zobrazení)
1. Skript `profile.php` načte všechny bloky pro dané `user_id` seřazené podle `position`.
2. Pro každý blok se provede `switch($type)`, který inkluduje příslušnou šablonu z `components/profile_blocks/`.
3. **Galerie**: Už nenačítá posty, ale bere URL přímo z pole `images` v JSONu.

### B. Management (Interaktivní Live Editor)
1. Vznikne nová stránka `profile_editor.php` pro vizuální úpravu bloků.
2. **Real-time náhled**: Změny v nastavení (počet sloupců, šířka bloku) se okamžitě projeví v náhledu pomocí JS.
3. **Výběr fotek**: Možnost nahrát 1-8 fotek specificky pro tento profilový blok.

## 4. Plán implementace

1.  **Refaktor Galerie**: Úprava `components/profile_blocks/gallery.php`, aby zobrazoval fotky z JSONu.
2.  **Live Editor**: Vytvoření `profile_editor.php` s interaktivními prvky.
3.  **Upload Logic**: Skript pro nahrávání fotek specificky do bloků profilu.
4.  **UI v Nastavení**: Přidání tlačítka "Upravit vizuálně" k blokům v `settings.php`.
