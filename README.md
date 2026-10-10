# NEO Website Check for Contao 5

**A free website check as a lead magnet.** A visitor enters a domain, the bundle measures the site with Google PageSpeed Insights, runs its own HTML, legal and AI-visibility checks, optionally lets an AI read the pages like a first-time visitor, grades everything with school grades and sends a six-page PDF report by e-mail — in exchange for first name, last name and e-mail address.

Built by [NEO Digital](https://neo-digital.at) (Erich Nigg, Graz) for its own website and released under the MIT license so any agency, freelancer or consultant can run it on their Contao site.

*Deutsche Fassung weiter unten: [Deutsch](#neo-website-check-für-contao-5-deutsch).*

---

## What the visitor sees

1. **Address field** – "Check now". Spam protection runs invisibly (ALTCHA on Contao ≥ 5.7, honeypot otherwise).
2. **Live progress** – six steps with ticks, the remaining seconds, and a screenshot of the checked site with markers on the findings. 60–120 seconds.
3. **Lead form** – first name, last name, e-mail (required), newsletter checkbox (optional, double opt-in via Brevo). Honest wording: no data, no result.
4. **Result on the page** – overall grade (1–5), four Lighthouse scores, seven graded areas, the three most important measures with effort, what already works, a "talk about it" button.
5. **E-mail with the PDF report** – and, if configured, a copy to you with the lead data in subject and body.

## What is checked

| Area | Source |
|---|---|
| Loading speed | PageSpeed Insights (mobile + desktop start page, up to two sub-pages): LCP, CLS, TBT, INP (field data), page weight, largest image, render-blocking files, image formats, server response time |
| Accessibility | Lighthouse accessibility audits with the offending elements; contrast findings are cut out of the full-page screenshot and marked in red in the report |
| Findability on Google | Lighthouse SEO + own checks per page: title/description length, exactly one H1, heading order, sitemap, canonical, http→https redirect, structured data |
| Visibility in AI search | `llms.txt` / `llms-full.txt`, AI crawlers (GPTBot, ClaudeBot, PerplexityBot, Google-Extended, …) in `robots.txt`, identity schema (Organization/LocalBusiness/…), offer stated in the first sentences |
| Content & clarity | AI reading of the visible text (and the mobile screenshot): is it clear in five seconds what, for whom, where, and what the next step is? Strengths and issues in plain language. Without AI: heuristics |
| Legal & privacy | Imprint and privacy policy linked and reachable, consent tool present, tracking/embeds loaded before consent (from the Lighthouse network list). Wording: technical check, not legal advice |
| Mobile | Viewport, tap targets, font size, content width |

Grades: 1 ≥ 90 points, 2 ≥ 78, 3 ≥ 65, 4 ≥ 50, else 5. The overall grade is the average, but never better than the worst area plus one.

Pages: the start page plus up to four key sub-pages found in the navigation and the sitemap (offer, about, contact, legal). A site with 50 pages is represented by five, so the check stays within two minutes.

## Requirements

- Contao **5.3 or newer** (ALTCHA spam protection needs 5.7; older versions use a honeypot), PHP 8.2+, GD with JPEG and WebP support (standard).
- A **Google PageSpeed Insights API key** (free, see below). Without a key the public quota is shared and usually exhausted.
- A working **mailer** (`MAILER_DSN`) in Contao.
- Optional: an **OpenAI-compatible AI endpoint** (Requesty, OpenAI, Azure OpenAI, Ollama, …) for the "read like a visitor" step; **Gotenberg** for pixel-perfect PDFs; **Brevo** for newsletter double opt-in.

## Installation

**Contao Manager:** search for `neo-digital/contao-websitecheck-bundle`, install, run the database migration (update packages → database update).

**Command line:**

```bash
composer require neo-digital/contao-websitecheck-bundle
php vendor/bin/contao-console contao:migrate
```

Until the package is on Packagist, add the repository to your `composer.json` first:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/NEO-Digital-AT/NEO-Contao-Websitecheck" }
    ]
}
```

and require `neo-digital/contao-websitecheck-bundle:dev-main` (or a tag such as `^1.0`).

The bundle registers itself (Contao manager plugin), creates the table `tl_neo_websitecheck`, symlinks its assets to `public/bundles/neowebsitecheck/` and adds the content element **Website check** (group *NEO*) and the back-end module *NEO › Website checks*.

## Configuration

Create or extend `config/config.yaml`:

```yaml
neo_websitecheck:
    pagespeed:
        api_key: '%env(NEO_WEBSITECHECK_PSI_KEY)%'
    ai:
        base_url: 'https://router.requesty.ai/v1'   # any OpenAI-compatible endpoint
        api_key: '%env(NEO_WEBSITECHECK_AI_KEY)%'    # empty = skip the AI step
        model: 'anthropic/claude-sonnet-4-5'          # a vision-capable model for the screenshot
    mail:
        from_email: 'office@example.com'   # empty = Contao admin e-mail
        from_name: 'Example Agency'
        reply_to: 'erich@example.com'
        notify_email: 'erich@example.com' # your copy with the lead data; empty = none
    brand:
        name: 'Example Agency'
        owner_name: 'Erich Nigg'
        phone: '+43 316 123456'
        website_url: 'https://example.com'
        contact_url: 'https://example.com/contact'
        privacy_url: 'https://example.com/privacy'
        logo: 'files/theme/logo.png'       # PNG/JPG for PDF and e-mail (SVG is not supported by Dompdf)
        portrait: 'files/theme/erich.jpg'  # optional, "what happens next" box
        color_primary: '#2a025f'
        color_accent: '#a8f20d'
    limits:
        checks_per_ip_per_day: 2
        cache_hours: 24       # same domain again → stored result, no new measurement
        max_pages: 5
        retention_days: 90    # checks incl. lead data, screenshots and PDFs are deleted after this
    pdf:
        engine: dompdf        # dompdf | gotenberg | none
        gotenberg_url: ''     # e.g. http://gotenberg:3000
    newsletter:
        provider: none        # none | brevo
        brevo_api_key: '%env(BREVO_API_KEY)%'
        brevo_list_id: 0
        brevo_template_id: 0  # double-opt-in template
        brevo_redirect_url: 'https://example.com/newsletter/confirmed'
    tracking:
        data_layer: true      # push events into window.dataLayer
```

Every key is optional; the defaults above (minus the keys) are built in. Add the secrets to `.env.local`:

```
NEO_WEBSITECHECK_PSI_KEY=AIza...
NEO_WEBSITECHECK_AI_KEY=...
BREVO_API_KEY=...
```

### Google PageSpeed Insights API key (free)

1. Open the [Google Cloud Console](https://console.cloud.google.com/), create a project (or pick one).
2. *APIs & Services → Library* → search **PageSpeed Insights API** → *Enable*.
3. *APIs & Services → Credentials → Create credentials → API key*. Restrict the key to the PageSpeed Insights API (recommended).
4. Put the key into `NEO_WEBSITECHECK_PSI_KEY`.

The free quota is 25,000 queries per day; one check uses three to four queries.

### AI endpoint (Requesty or any OpenAI-compatible API)

The AI step sends the visible text of the checked pages and the mobile screenshot to a chat-completions endpoint and expects JSON back. Any OpenAI-compatible API works.

**Requesty** (router with many models, EU hosting available):

1. Create an account at [requesty.ai](https://www.requesty.ai/), open *API keys*, create a key.
2. `base_url: 'https://router.requesty.ai/v1'`, `api_key` = your key, `model` = a model id from the Requesty model list (e.g. `anthropic/claude-sonnet-4-5` or `openai/gpt-4o`). Choose a vision-capable model when `send_screenshot` is on.

**OpenAI:** `base_url: 'https://api.openai.com/v1'`, `model: 'gpt-4o-mini'`.
**Ollama (local):** `base_url: 'http://ollama:11434/v1'`, `api_key: 'ollama'`, `model: 'llama3.2-vision'`.

Cost per check is usually a few cents (about 15–25k input tokens). Leave `api_key` empty to run without AI: the content area then uses heuristics and the step is skipped in the progress list.

### Mail

Reports go through Contao's mailer (`MAILER_DSN` in `.env`). The visitor receives the report with the PDF attached; `notify_email` receives a second e-mail with the same PDF plus name, e-mail, domain, grade and newsletter choice (a Bcc could not carry that information).

### PDF

- **dompdf** (default): pure PHP, no extra service, DejaVu Sans fonts, umlauts fine. The report template is written in Dompdf-compatible HTML (tables, no flexbox).
- **gotenberg**: Chromium as a container (`docker run --rm -p 3000:3000 gotenberg/gotenberg:8`), renders the same template pixel-perfect with your fonts. Set `engine: gotenberg` and `gotenberg_url`.
- **none**: no PDF; the e-mail still contains the grade and the measures.

### Newsletter (Brevo double opt-in)

1. In Brevo: *Contacts → Lists* → note the **list id**. *Campaigns → Templates* → create a **double-opt-in template** (type "Double opt-in confirmation"), note its id.
2. `provider: brevo`, `brevo_list_id`, `brevo_template_id`, `brevo_redirect_url` (page shown after confirming).
3. The checkbox text can be changed with `newsletter.label` or the translation key `fe.newsletter`.

Only when the visitor ticks the box, the bundle calls Brevo's double-opt-in endpoint; the visitor becomes a subscriber after clicking the confirmation link.

## Back-end settings (brand, design, texts)

Everything a visitor sees can be changed in the back end under **NEO › Website check settings**, without touching `config.yaml` (back-end values win, empty fields fall back to the YAML):

- **Who sends the report:** company/agency, contact person, phone, website, contact page (target of the "talk about it" buttons), privacy policy page.
- **Design:** logo (PNG/JPG/SVG), logo for dark backgrounds, portrait photo, primary and accent colour. The colours are applied to the report, the e-mail and the element on the page.
- **E-mail:** sender name and address, reply-to, copy address for the operator.
- **Limits:** checks per visitor and day, cache hours, retention days, pages per check.
- **Texts (per language):** the customer-facing texts of the PDF report, the e-mail and the element — cover title, offer, promise, subject, greeting, intro, button labels, footer, lead form, disclaimer. Empty fields keep the bundle's text. Placeholders work in every field: `{{firstname}}`, `{{lastname}}`, `{{domain}}`, `{{grade}}`, `{{grade_word}}`, `{{pages}}`, `{{company}}`, `{{owner}}`, `{{phone}}`, `{{website}}`, `{{days}}`, `{{date}}`.
- **Preview PDF / Preview e-mail:** render the templates with the newest finished check or, before the first check, with bundled demo data — in a new tab, right after saving.

API keys, the PDF engine and the newsletter provider stay in `config.yaml` (they are technical, not editorial). Non-admin groups need the module and the tables `tl_neo_websitecheck_settings` and `tl_neo_websitecheck_text`.

## Usage

1. Back end → *Articles* → add the content element **Website check** (group *NEO*).
2. Fill **headline** and **text** (intro above the form). **Redirect page**: the page the "talk about the result" button leads to (fallback: `brand.contact_url`).
3. Put the element on a landing page, your web-design page or a dedicated `/website-check` page.

**Test the setup from the command line** (no browser, no limits):

```bash
php vendor/bin/contao-console neo:websitecheck:run --url=https://example.com --locale=de
php vendor/bin/contao-console neo:websitecheck:run 42 --json   # run/inspect one check
php vendor/bin/contao-console neo:websitecheck:run             # advance all pending checks
```

## How it runs (no worker needed)

The check is a resumable step machine (`discover → speed_home → speed_pages → analyze → ai → finish`). Each step is short enough to run inside one HTTP request: the visitor's browser polls `/_neo/websitecheck/{id}/status` every two seconds, and the request that finds the check idle executes the next step. Nothing else is required — it works on shared hosting. Additionally, the Contao cron (`contao:cron`, minutely) advances checks that a visitor abandoned, and the daily cron deletes checks older than `retention_days`. If you prefer a real worker, run `neo:websitecheck:run` from a cron job or supervisor.

Results are cached for `cache_hours`: a second visitor entering the same domain gets the stored result immediately (and still has to leave their data). Screenshots and PDFs live in `var/neo-websitecheck/<id>/`, outside the web root; the live preview serves them through a token-protected route.

## Customising

- **Templates:** copy any file from `contao/templates/` of the bundle to `templates/` of your project (e.g. `templates/neo_websitecheck/report.html.twig`, `templates/content_element/neo_websitecheck.html.twig`) and adapt it. The report template receives `result`, `areas`, `brand`, `images`, `crops`, `date`, …
- **Styles:** the element uses CSS custom properties (`--nwc-bg`, `--nwc-accent`, `--nwc-fg`, `--nwc-radius`, …). Override them on `.content-neo-websitecheck .nwc` in your theme.
- **Texts:** every string lives in `translations/neo_websitecheck.{en,de}.yaml`. Override single keys in your project's `translations/` folder.
- **Grading:** `Check\Grader` holds the scoring; `Check\CheckRunner` the steps. Decorate or replace the services as usual in Symfony.

## Analytics events

The JavaScript dispatches a DOM event `neo_websitecheck` on `document` (`event.detail = {name, params}`) and, with `tracking.data_layer: true`, pushes `{event: name, …params}` into `window.dataLayer`:

| Event | When | Parameters |
|---|---|---|
| `check_start` | address submitted | `domain`, `cached` |
| `check_gate` | result ready, lead form shown | `domain` |
| `check_abandon` | page left while the lead form was open | `domain` |
| `check_lead` | lead form submitted | `domain`, `newsletter` (0/1) |
| `check_result` | result shown | `domain`, `grade` |

Example without GTM, sending to GA4 only after consent:

```js
document.addEventListener('neo_websitecheck', function (e) {
  if (window.myConsent && window.myConsent('statistics') && typeof gtag === 'function') {
    gtag('event', e.detail.name, e.detail.params);
  }
});
```

## Back end and permissions

*NEO › Website checks* lists every check with grade, status, name, e-mail and date; a row can be opened, deleted or its report re-sent. The table name is `tl_neo_websitecheck` — give non-admin user groups the module and the table permissions if they should see it.

## Privacy

The bundle stores: domain, measurement data, first name, last name, e-mail, newsletter choice, a daily-rotating hash of the IP address (rate limit), screenshots and the PDF. Everything is deleted after `retention_days` by the cron job. Add the check to your privacy policy (purpose: sending the requested report; processors: Google PageSpeed Insights, your AI provider, your mail provider, Brevo if enabled).

## Troubleshooting

- **"Quota exceeded" from PageSpeed** – no or wrong API key; the public quota is shared with the whole world. Add your own key.
- **Check fails at "speed_home"** – the site blocks Google's crawler, needs a login or is not reachable from the internet. The step is retried once, then skipped; the report says so.
- **No e-mail** – check `MAILER_DSN` and the Contao log (`var/logs`); the result page shows a notice when sending failed and the back end has a re-send button.
- **Umlauts missing in the PDF** – a custom template uses a font Dompdf does not have; keep `DejaVu Sans` or switch to Gotenberg.
- **ALTCHA widget not visible / spam error** – ALTCHA needs HTTPS (or localhost); on Contao < 5.7 the honeypot is used automatically.

## License

MIT — see [LICENSE](LICENSE).

---

# NEO Website-Check für Contao 5 (Deutsch)

**Ein kostenloser Website-Check als Lead-Magnet.** Ein Besucher gibt seine Domain ein, das Bundle misst die Seite mit Google PageSpeed Insights, prüft HTML, Rechtliches und KI-Sichtbarkeit selbst, lässt optional eine KI die Seiten wie einen neuen Besucher lesen, benotet alles mit Schulnoten und schickt einen sechsseitigen PDF-Bericht per E-Mail – im Tausch gegen Vorname, Nachname und E-Mail-Adresse.

Entwickelt von [NEO Digital](https://neo-digital.at) (Erich Nigg, Graz) für die eigene Website, als MIT-Lizenz freigegeben, damit jede Agentur, jeder Freelancer und Berater es auf der eigenen Contao-Seite einsetzen kann.

## Was der Besucher sieht

1. **Adressfeld** – „Jetzt prüfen“. Spam-Schutz läuft unsichtbar (ALTCHA ab Contao 5.7, sonst Honeypot).
2. **Live-Fortschritt** – sechs Schritte mit Häkchen, Restzeit in Sekunden, Screenshot der geprüften Seite mit Markierungen an den Fundstellen. 60–120 Sekunden.
3. **Kontaktformular** – Vorname, Nachname, E-Mail (Pflicht), Newsletter-Häkchen (freiwillig, Double-Opt-in über Brevo). Ehrlich formuliert: ohne Daten kein Ergebnis.
4. **Ergebnis auf der Seite** – Gesamtnote (1–5), vier Lighthouse-Werte, sieben benotete Bereiche, die drei wichtigsten Maßnahmen mit Aufwand, was bereits passt, ein Knopf „Ergebnis besprechen“.
5. **E-Mail mit PDF-Bericht** – und, wenn konfiguriert, eine Kopie an Sie mit den Kontaktdaten in Betreff und Text.

## Was geprüft wird

| Bereich | Quelle |
|---|---|
| Ladezeit | PageSpeed Insights (Startseite mobil + Desktop, bis zu zwei Unterseiten): LCP, CLS, TBT, INP (Felddaten), Seitengewicht, größtes Bild, blockierende Dateien, Bildformate, Serverantwort |
| Barrierefreiheit | Lighthouse-Prüfungen mit den betroffenen Elementen; Kontrast-Fundstellen werden aus dem Vollbild-Screenshot ausgeschnitten und im Bericht rot markiert |
| Auffindbarkeit bei Google | Lighthouse SEO plus eigene Prüfungen je Seite: Titel-/Beschreibungslänge, genau eine H1, Überschriftenreihenfolge, Sitemap, Canonical, http→https, strukturierte Daten |
| Sichtbarkeit in KI-Suchen | `llms.txt` / `llms-full.txt`, KI-Crawler (GPTBot, ClaudeBot, PerplexityBot, Google-Extended, …) in der `robots.txt`, Identitäts-Schema (Organization/LocalBusiness/…), Angebot in den ersten Sätzen |
| Inhalte & Klarheit | KI-Lesung des sichtbaren Textes (und des Handy-Screenshots): Ist in fünf Sekunden klar, was, für wen, wo und was der nächste Schritt ist? Stärken und Mängel in Laiensprache. Ohne KI: Heuristiken |
| Recht & Datenschutz | Impressum und Datenschutzerklärung verlinkt und erreichbar, Consent-Tool vorhanden, Tracking/Einbettungen vor der Einwilligung (aus der Lighthouse-Netzwerkliste). Formulierung: technische Prüfung, keine Rechtsberatung |
| Mobil | Viewport, Tippziele, Schriftgröße, Inhaltsbreite |

Noten: 1 ab 90 Punkten, 2 ab 78, 3 ab 65, 4 ab 50, sonst 5. Die Gesamtnote ist der Durchschnitt, aber nie besser als der schlechteste Bereich plus eins.

Seiten: Startseite plus bis zu vier wichtige Unterseiten aus Navigation und Sitemap (Angebot, Über uns, Kontakt, Rechtliches). Eine Seite mit 50 Unterseiten wird durch fünf vertreten, damit der Check in zwei Minuten fertig ist.

## Voraussetzungen

- Contao **5.3 oder neuer** (ALTCHA ab 5.7, davor Honeypot), PHP 8.2+, GD mit JPEG und WebP (Standard).
- Ein **Google-PageSpeed-Insights-API-Schlüssel** (kostenlos, siehe unten). Ohne Schlüssel ist das öffentliche Kontingent meist erschöpft.
- Ein funktionierender **Mailer** (`MAILER_DSN`) in Contao.
- Optional: ein **OpenAI-kompatibler KI-Endpunkt** (Requesty, OpenAI, Azure OpenAI, Ollama, …) für die Lesung; **Gotenberg** für pixelgenaue PDFs; **Brevo** für den Newsletter-Double-Opt-in.

## Installation

**Contao Manager:** nach `neo-digital/contao-websitecheck-bundle` suchen, installieren, Datenbank aktualisieren.

**Konsole:**

```bash
composer require neo-digital/contao-websitecheck-bundle
php vendor/bin/contao-console contao:migrate
```

Solange das Paket nicht auf Packagist liegt, zuerst das Repository in die `composer.json` eintragen:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/NEO-Digital-AT/NEO-Contao-Websitecheck" }
    ]
}
```

und `neo-digital/contao-websitecheck-bundle:dev-main` (oder ein Tag wie `^1.0`) anfordern.

Das Bundle registriert sich selbst (Contao-Manager-Plugin), legt die Tabelle `tl_neo_websitecheck` an, verlinkt seine Assets nach `public/bundles/neowebsitecheck/` und bringt das Inhaltselement **Website-Check** (Gruppe *NEO*) sowie das Backend-Modul *NEO › Website-Checks* mit.

## Konfiguration

Die vollständige Beispielkonfiguration steht oben im englischen Teil (`config/config.yaml`, Abschnitt *Configuration*); die Schlüssel sind identisch. Kurz:

- `pagespeed.api_key` – Google-Schlüssel (Pflicht für verlässliche Messungen).
- `ai.*` – KI-Endpunkt, Schlüssel, Modell; leer lassen = Schritt wird übersprungen.
- `mail.*` – Absender, Antwortadresse, `notify_email` für Ihre Kopie mit den Kontaktdaten.
- `brand.*` – Name, Person, Telefon, Website, Kontakt- und Datenschutzseite, Logo (PNG/JPG), Porträt, zwei Farben.
- `limits.*` – Checks je IP und Tag (2), Cache (24 h), Seiten (5), Aufbewahrung (90 Tage).
- `pdf.*` – `dompdf` (Standard), `gotenberg` oder `none`.
- `newsletter.*` – Brevo-Listen-ID, Double-Opt-in-Vorlage, Weiterleitung.
- `tracking.data_layer` – Ereignisse in `window.dataLayer`.

### Google-PageSpeed-Schlüssel (kostenlos)

1. [Google Cloud Console](https://console.cloud.google.com/) öffnen, Projekt anlegen oder wählen.
2. *APIs & Dienste → Bibliothek* → **PageSpeed Insights API** → *Aktivieren*.
3. *APIs & Dienste → Anmeldedaten → Anmeldedaten erstellen → API-Schlüssel*; den Schlüssel auf die PageSpeed-API einschränken.
4. Schlüssel in `NEO_WEBSITECHECK_PSI_KEY` eintragen. Kontingent: 25 000 Abfragen am Tag, ein Check braucht drei bis vier.

### KI-Endpunkt (Requesty oder jede OpenAI-kompatible API)

**Requesty:** Konto auf [requesty.ai](https://www.requesty.ai/) anlegen, unter *API keys* einen Schlüssel erzeugen. `base_url: 'https://router.requesty.ai/v1'`, `model` = eine Modell-ID aus der Requesty-Liste (z. B. `anthropic/claude-sonnet-4-5`). Für den Screenshot ein Modell mit Bildverständnis wählen.
**OpenAI:** `base_url: 'https://api.openai.com/v1'`, `model: 'gpt-4o-mini'`.
**Ollama (lokal):** `base_url: 'http://ollama:11434/v1'`, `api_key: 'ollama'`.

Kosten je Check meist wenige Cent. Ohne Schlüssel läuft der Check ohne KI.

### E-Mail, PDF, Newsletter

- Versand über Contaos Mailer (`MAILER_DSN`). Der Besucher bekommt den Bericht mit PDF; `notify_email` bekommt eine zweite E-Mail mit demselben PDF plus Name, E-Mail, Domain, Note und Newsletter-Entscheidung.
- PDF: `dompdf` (reines PHP), `gotenberg` (Chromium-Container, `docker run --rm -p 3000:3000 gotenberg/gotenberg:8`) oder `none`.
- Newsletter: In Brevo die Listen-ID und eine Double-Opt-in-Vorlage anlegen, IDs eintragen, `provider: brevo`. Nur bei gesetztem Häkchen ruft das Bundle den Double-Opt-in-Endpunkt auf; angemeldet ist der Besucher erst nach dem Klick auf den Bestätigungslink.

## Backend-Einstellungen (Marke, Design, Texte)

Alles, was ein Besucher sieht, lässt sich im Backend unter **NEO › Website-Check Einstellungen** ändern, ohne `config.yaml` anzufassen (Backend-Werte gewinnen, leere Felder fallen auf die YAML zurück):

- **Wer den Bericht verschickt:** Firma/Agentur, Ansprechperson, Telefon, Website, Kontaktseite (Ziel der Knöpfe „Ergebnis besprechen“), Datenschutzseite.
- **Design:** Logo (PNG/JPG/SVG), Logo für dunklen Hintergrund, Porträtfoto, Haupt- und Akzentfarbe. Die Farben gelten für Bericht, E-Mail und das Element auf der Seite.
- **E-Mail:** Absendername und -adresse, Antwortadresse, Kopie an den Betreiber.
- **Grenzen:** Checks je Besucher und Tag, Zwischenspeicher, Aufbewahrung, Seiten je Check.
- **Texte (je Sprache):** die kundensichtbaren Texte von PDF-Bericht, E-Mail und Element: Deckblatt-Titel, Angebot, Zusage, Betreff, Anrede, Einleitung, Knöpfe, Fußzeile, Kontaktformular, Hinweis. Leere Felder behalten den mitgelieferten Text. Platzhalter in jedem Feld: `{{firstname}}`, `{{lastname}}`, `{{domain}}`, `{{grade}}`, `{{grade_word}}`, `{{pages}}`, `{{company}}`, `{{owner}}`, `{{phone}}`, `{{website}}`, `{{days}}`, `{{date}}`.
- **Vorschau PDF / Vorschau E-Mail:** rendern die Vorlagen mit dem neuesten fertigen Check oder, vor dem ersten Check, mit Beispieldaten, in einem neuen Tab direkt nach dem Speichern.

API-Schlüssel, PDF-Engine und Newsletter-Anbieter bleiben in der `config.yaml` (technisch, nicht redaktionell). Nicht-Admin-Gruppen brauchen das Modul und die Tabellen `tl_neo_websitecheck_settings` und `tl_neo_websitecheck_text`.

## Verwendung

1. Backend → *Artikel* → Inhaltselement **Website-Check** (Gruppe *NEO*) einfügen.
2. **Überschrift** und **Text** (Einleitung über dem Formular) ausfüllen; **Weiterleitungsseite** = Zielseite des Knopfs „Ergebnis besprechen“ (sonst `brand.contact_url`).
3. Element auf der Landingpage, der Webdesign-Seite oder einer eigenen Seite `/website-check` einsetzen.

**Einrichtung ohne Browser testen:**

```bash
php vendor/bin/contao-console neo:websitecheck:run --url=https://example.com --locale=de
```

## So läuft der Check (kein Worker nötig)

Der Check ist ein wiederaufnehmbarer Schrittablauf (`discover → speed_home → speed_pages → analyze → ai → finish`). Jeder Schritt ist kurz genug für einen HTTP-Request: Der Browser des Besuchers fragt alle zwei Sekunden den Status ab, und der Request, der den Check untätig vorfindet, führt den nächsten Schritt aus. Das funktioniert auch auf Shared Hosting. Zusätzlich treibt der Contao-Cron (minütlich) abgebrochene Checks weiter und löscht täglich alles, was älter als `retention_days` ist. Wer einen echten Worker will, ruft `neo:websitecheck:run` per Cron auf.

Ergebnisse werden `cache_hours` lang gespeichert: Wer dieselbe Domain noch einmal eingibt, bekommt das gespeicherte Ergebnis sofort (und muss trotzdem seine Daten hinterlassen). Screenshots und PDFs liegen in `var/neo-websitecheck/<id>/`, außerhalb des Web-Roots.

## Anpassen

- **Vorlagen:** Dateien aus `contao/templates/` des Bundles nach `templates/` im Projekt kopieren (z. B. `templates/neo_websitecheck/report.html.twig`).
- **Stile:** CSS-Variablen (`--nwc-bg`, `--nwc-accent`, `--nwc-fg`, `--nwc-radius`, …) auf `.content-neo-websitecheck .nwc` überschreiben.
- **Texte:** alle Texte in `translations/neo_websitecheck.{en,de}.yaml`; einzelne Schlüssel im Projekt unter `translations/` überschreiben.

## Tracking-Ereignisse

DOM-Ereignis `neo_websitecheck` auf `document` (`event.detail = {name, params}`) und, mit `tracking.data_layer: true`, `window.dataLayer.push({event, …})`: `check_start`, `check_gate`, `check_abandon`, `check_lead` (mit `newsletter` 0/1), `check_result` (mit `grade`). Beispiel für GA4 nur nach Einwilligung siehe oben im englischen Teil.

## Backend und Rechte

*NEO › Website-Checks* listet jeden Check mit Note, Status, Name, E-Mail und Datum; Details ansehen, löschen oder Bericht erneut senden. Tabellenname `tl_neo_websitecheck` – Benutzergruppen ohne Admin-Rechte brauchen das Modul und die Tabellenrechte.

## Datenschutz

Gespeichert werden Domain, Messdaten, Vorname, Nachname, E-Mail, Newsletter-Entscheidung, ein täglich wechselnder Hash der IP-Adresse (Limit), Screenshots und das PDF. Alles wird nach `retention_days` vom Cron gelöscht. Den Check in die Datenschutzerklärung aufnehmen (Zweck: Zusendung des angeforderten Berichts; Auftragsverarbeiter: Google PageSpeed Insights, KI-Anbieter, Mail-Anbieter, ggf. Brevo).

## Fehlersuche

- **„Quota exceeded“ von PageSpeed** – kein oder falscher API-Schlüssel.
- **Check bricht bei „speed_home“ ab** – die Seite blockiert Googles Crawler, braucht ein Login oder ist nicht erreichbar. Der Schritt wird einmal wiederholt, dann übersprungen.
- **Keine E-Mail** – `MAILER_DSN` und `var/logs` prüfen; die Ergebnisseite zeigt einen Hinweis, das Backend hat einen Knopf „erneut senden“.
- **Umlaute fehlen im PDF** – eigene Vorlage mit einer Schrift, die Dompdf nicht kennt; `DejaVu Sans` behalten oder Gotenberg verwenden.
- **ALTCHA nicht sichtbar / Spam-Fehler** – ALTCHA braucht HTTPS (oder localhost); unter Contao 5.7 greift automatisch der Honeypot.

## Lizenz

MIT – siehe [LICENSE](LICENSE).
