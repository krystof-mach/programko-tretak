# TODO - Chybějící funkce a vylepšení

Tento seznam obsahuje úkoly potřebné pro plné splnění specifikací (`specs.md` a `specs2.md`) a další doporučená vylepšení.

## 1. Fotografie a Vodoznaky (Požadavek 7)
- [x] Implementovat automatické vkládání poloprůhledného vodoznaku do náhledových fotografií v klientské zóně.
- [x] Zajistit, aby originály bez vodoznaku byly dostupné ke stažení (v .zip) až po označení zakázky jako "Zaplaceno".

## 2. Hodnocení a Recenze (Požadavek 9)
- [x] Vytvořit databázovou tabulku `reviews` (id, booking_id, photographer_id, user_id, rating, comment, approved, created_at).
- [x] Implementovat hvězdičkové hodnocení (1-5) a slovní referenci pro klienty po dokončení zakázky.
- [x] Přidat do profilu fotografa sekci s referencemi a zobrazení průměrného hodnocení.
- [x] Implementovat schvalování recenzí autorem (adminem) před jejich zveřejněním.

## 3. Blog a Markdown (Požadavek 8)
- [x] Integrovat knihovnu pro parsování Markdownu (např. `Parsedown.php`) pro články.
- [x] Implementovat extrakci EXIF metadat z nahraných fotografií (clona, čas, ISO) a jejich zobrazení u článků.
- [x] Přidat sekci diskuse/komentáře pod články pro registrované uživatele.

## 4. Rozšířený Dashboard Fotografa (Požadavek 10)
- [x] Přidat statistiky příjmů z realizovaných zakázek.
- [x] Zobrazit průměrné hodnocení a počet stažení souborů klienty.
- [x] Implementovat upozornění na docházející kapacitu úložiště (kvóta v GB).
- [x] Přidat přehled nejbližších termínů z kalendáře přímo na dashboard.

## 5. Klientská zóna a Unikátní odkazy (Požadavek 7)
- [x] Generování unikátních hashovaných odkazů pro hosty (neregistrované uživatele) pro přístup k jejich zakázkám.
- [x] Vytvoření dedikované stránky zakázky s lightboxy pro prohlížení náhledů.

## 6. Vyhledávání a Filtrování (Požadavek 3 & 5)
- [x] Vylepšit algoritmus vyhledávání fotografů – upřednostnit autory s lepším hodnocením.
- [x] Přidat pokročilé filtrování služeb podle lokality a ceny na hlavní stránce.

## 7. Technické a Bezpečnostní (Požadavek 1)
- [ ] Provést audit proti CSRF útokům a implementovat tokeny do formulářů.
- [ ] Optimalizovat nahrávání velkých souborů (chunk upload) pro stabilnější cloudové úložiště.
