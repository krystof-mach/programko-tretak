1\) Stránka bude ochráněna před kybernetickými útoky typu:

\- SQL Injection

-Xsite scripting

a to dle metod které probereme v hodinách nebo dle metod které si sami

vyhledáte.



2\) Stránka bude optimalizována pro zobrazení na:

-PC

-Mobilní zařízení



3\) Web slouží jako komplexní platforma pro fotografy (autory) a jejich klienty. Propojuje prezentační část (portfolio, blog) s procesní částí (objednávkový systém) a technickou částí (cloudové úložiště hotových zakázek). Návštěvník může procházet nabídky focení a filtrovat je podle kategorie, ceny, lokality nebo jména fotografa.



4\) Klient může vytvořit objednávku buď jako registrovaný (základní údaje se ve formuláři předvyplní), nebo jako host (vyplňuje formulář s kontakty). Nezávisle na přihlášení pak zákazník vybírá termín z kalendáře, vyplňuje lokalitu, typ focení (typ služby - na výběr z bodů, příp. jiné) a jiné požadavky. Po odeslání vzniká v databázi záznam ve stavu „Čeká na schválení“. Fotograf obdrží notifikaci a v administračním rozhraní termín potvrdí, zamítne nebo navrhne změnu, čímž se mění stav objednávky.



5\) Každý fotograf má vlastní profilovou stránku, kterou si může přizpůsobit. Může si vybrat, které moduly zobrazí (např. galerie (nejlepších prací), ceník služeb, text/bio, kalendář obsazenosti nebo sekce s referencemi). Každý typ focení (např. Svatba, Portrét, Ateliér) je definován jako produkt s kategorií, cenou, popisem a ukázkovým obrázkem. Návštěvník může tyto služby filtrovat, aby rychle našel to, co hledá. Data o vzhledu a obsahu profilu jsou uložena v databázi a dynamicky generována pro návštěvníka.



6\) Fotograf má k dispozici soukromé úložiště pro své soubory. U každé složky/zakázky může nastavit úroveň přístupu: "Soukromé" (jen pro autora), "Sdílené s klientem" (přístupné přes unikátní hashovaný odkaz nebo heslo) nebo "Veřejné" (součást portfolia). Systém musí řešit efektivní nahrávání (upload) a bezpečné stahování (download) fotografií v plném rozlišení.



7\) Pro každou zakázku systém vygeneruje unikátní stránku. Klient se na ni dostane buď přes svůj účet, nebo (pokud je host) přes unikátní hashovaný odkaz a heslo, které mu vygeneruje a odešle (simulovaně/e-mailem/notifikací) systém/autor. Zde si klient může hotové fotky prohlédnout a hromadně stáhnout.

Po dokončení focení nahraje fotograf snímky do dedikované sekce zakázky. Zákazník si skrze unikátní URL odkaz může fotky prohlédnout v náhledu (lightboxy). Všechny náhledové fotografie v klientské zóně jsou automaticky opatřeny poloprůhledným vodoznakem autora. Teprve po dokončení zakázky a jejím označení jako "Zaplaceno" se klientovi zpřístupní sekce pro stažení finálních fotografií v plném rozlišení bez vodoznaku (v .zip formátu).



8\) Autoři mohou publikovat odborné články, návody a tipy a triky z focení (Markdown podpora). Ke článkům mohou autoři přikládat fotografie, u kterých systém zobrazuje i technické parametry (clona, čas, ISO), pokud jsou dostupná v metadatech snímku ( což slouží jako edukativní prvek pro začínající fotografy.). Ostatní registrovaní uživatelé mohou pod články diskutovat a sdílet tipy. Články jsou provázány s profilem autora. Ostatní registrovaní uživatelé mohou pod články diskutovat.



9\) Po uzavření zakázky může klient (i host přes unikátní odkaz) udělit fotografovi hvězdičkové hodnocení a slovní referenci, která se po schválení autorem zobrazí na jeho profilu jako marketingový prvek. Tato data jsou veřejně viditelná na profilu fotografa a ovlivňují jeho pořadí ve výsledcích vyhledávání (algoritmus upřednostňuje autory s lepším hodnocením).



10\) Fotograf ve svém rozhraní vidí přehledný dashboard: počet aktivních zakázek, průměrné hodnocení, statistiky příjmů z realizovaných zakázek, počet stažení souborů klienty, přehled nejbližších termínů v kalendáři a upozornění na docházející kapacitu jeho úložiště (kvóta na GB dat).

