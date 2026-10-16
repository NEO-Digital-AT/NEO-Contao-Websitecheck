# NEO Website Check for Contao 5

**A free website check as a lead magnet.** A visitor enters a domain, the bundle measures the site with Google PageSpeed Insights, runs its own HTML, legal and AI-visibility checks, optionally lets an AI read the pages like a first-time visitor, grades everything with school grades and sends a six-page PDF report by e-mail — in exchange for first name, last name and e-mail address.

Built for a production Contao website. Commercial license — see [License](#license).

*Deutsche Fassung weiter unten: [Deutsch](#neo-website-check-für-contao-5-deutsch).*

---

## What the visitor sees

1. **Address field** – "Check now". Spam protection runs invisibly (ALTCHA on Contao ≥ 5.7, honeypot otherwise).
2. **Live progress** – six steps with ticks, the remaining seconds, and a screenshot of the checked site with markers on the findings. 60–120 seconds.
3. **Lead form** – first name, last name, e-mail (required), permission for one follow-up e-mail (optional, wording and time stored), newsletter checkbox (optional, double opt-in via the NEO Brevo module or Contao's newsletter module). Honest wording: no data, no result.
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

- **Contao 5.3 LTS up to 5.7** (`contao/core-bundle ^5.3`, enforced by Composer, so it cannot be installed on Contao 4) and **PHP 8.2+**. Tested on a fresh Contao 5.3 Managed Edition and on Contao 5.7.
- ALTCHA spam protection needs Contao 5.7; older versions use a honeypot. GD with JPEG and WebP support (standard).
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

Add the package repository you received with your license to your `composer.json` first:

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
        reply_to: 'office@example.com'
        notify_email: 'office@example.com' # your copy with the lead data; empty = none
    brand:
        name: 'Example Agency'
        owner_name: 'Max Mustermann'
        phone: '+43 316 123456'
        website_url: 'https://example.com'
        contact_url: 'https://example.com/contact'
        privacy_url: 'https://example.com/privacy'
        logo: 'files/theme/logo.png'       # PNG/JPG for PDF and e-mail (SVG is not supported by Dompdf)
        portrait: 'files/theme/portrait.jpg'  # optional, "what happens next" box
        color_primary: '#2a025f'
        color_accent: '#a8f20d'
    limits:
        checks_per_ip_per_hour: 2
        checks_per_ip_per_day: 10
        cache_hours: 24       # same domain again → stored result, no new measurement
        max_pages: 5
        retention_days: 90    # checks incl. lead data, screenshots and PDFs are deleted after this
    pdf:
        engine: dompdf        # dompdf | gotenberg | none
        gotenberg_url: ''     # e.g. http://gotenberg:3000
    newsletter:
        provider: auto        # auto | brevo | contao | none (auto: Brevo module if installed, else Contao newsletter)
    tracking:
        data_layer: true      # push events into window.dataLayer
```

Every key is optional; the defaults above (minus the keys) are built in. **Keys belong in the back end:** *Website check → Settings → Keys and AI* (PageSpeed key, AI key, AI model, provider address; keys stored encrypted). The configuration below is only the fallback for empty fields — existing values are taken over into the back end once by a migration. If you prefer the environment, add the secrets to `.env.local`:

```
NEO_WEBSITECHECK_PSI_KEY=AIza...
NEO_WEBSITECHECK_AI_KEY=...
BREVO_API_KEY=...
```

### Google PageSpeed Insights API key (free)

1. Open the [Google Cloud Console](https://console.cloud.google.com/), create a project (or pick one).
2. *APIs & Services → Library* → search **PageSpeed Insights API** → *Enable*.
3. *APIs & Services → Credentials → Create credentials → API key*. Restrict the key to the PageSpeed Insights API (recommended).
4. Enter the key in *Settings → Keys and AI* (or `NEO_WEBSITECHECK_PSI_KEY`).

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

- **dompdf** (default): pure PHP, no extra service. Default font is **Arimo** (bundled, looks and measures like Arial, covers Latin, Greek and Cyrillic); DejaVu Sans/Serif and Helvetica are available under *Design › Font*. Dompdf converts Arimo once into `<storage_dir>/fonts`. **Logos in e-mails:** mail programs show no SVG; place a PNG with the same name next to an SVG logo (`logo.svg` → `logo.png`, about 600 px wide) — e-mails use it, the PDF keeps the SVG. The report template is written in Dompdf-compatible HTML (tables, no flexbox). **Own fonts and images:** a custom template may load TrueType fonts and images from the Contao files directory with a relative path — `@font-face { font-family: 'Brand'; src: url('files/fonts/Brand-Regular.ttf'); }` or `<img src="files/images/badge.png">`. Use static `.ttf` files (no WOFF2, no variable fonts) and register normal and bold separately.
- **gotenberg**: Chromium as a container (`docker run --rm -p 3000:3000 gotenberg/gotenberg:8`), renders the same template pixel-perfect with your fonts. Set `engine: gotenberg` and `gotenberg_url`.
- **none**: no PDF; the e-mail still contains the grade and the measures.

### Newsletter (double opt-in)

No API key in a configuration file or the environment — the check uses the newsletter system the site already has:

- **NEO Brevo module installed** (`neo-digital/contao-brevo-bundle`): Brevo, with the API key stored in that module (*System → Settings → Brevo Newsletter*). Per configuration (= language) choose the **list**, the **double-opt-in template** (type "Double opt-in confirmation" in Brevo) and the **confirmation page** — lists and templates are read live from the account.
- **Otherwise Contao's newsletter module** (`contao/newsletter-bundle`): per configuration choose the **newsletter channel** and the **confirmation page** — a page with Contao's *Subscribe* module. The visitor gets Contao's confirmation e-mail (text from that Subscribe module's *subscription message*, else Contao's default) and is activated when clicking the link, exactly like Contao's own sign-up.
- Neither installed, or `provider: none`: no checkbox, everything else works.

Without a confirmation page there is no checkbox. The checkbox text can be changed with `newsletter.label` or the translation key `fe.newsletter`. Only when the visitor ticks the box a sign-up is started; the visitor becomes a subscriber after clicking the confirmation link.

## Back-end settings and the visual designer

Everything a visitor sees is edited in the back end under **NEO › Website check settings**, without touching `config.yaml` (back-end values win, empty fields fall back to the YAML).

**Settings (one record):**

- **Who sends the report:** company/agency, contact person, phone, website, contact page (target of the "talk about it" buttons), privacy policy page.
- **Design:** logo (PNG/JPG/SVG), logo for dark backgrounds, portrait photo, primary and accent colour.
- **E-mail:** default sender name and address, reply-to, copy address for the operator.
- **Limits:** checks per visitor and hour (default 2) and day (default 10), cache hours, retention days, pages per check.

**Configurations (one per language or use case):** "Edit configurations" opens the list. Each configuration has a name, a language and:

- **E-mail:** subject, sender, reply-to, and the **e-mail designer**.
- **PDF report:** file name with placeholders (`website-check-{{domain}}-{{date}}.pdf`) and the **PDF designer**.
- **Your copy:** subject of the operator copy.
- **The element on the page:** short result (grade, one sentence, PDF hint; default) or the full overview, and the labels of form, lead form and result.
- **Internal copy (administrators only):** an optional Twig template for the operator copy.

**The designer** (same for e-mail and PDF) has three panes:

- **Left:** *Blocks* to click or drag in (cover, heading, text, image, grade, rings, areas, measures, area detail, table of pages, next steps, signature, page break …), *Structure* to reorder by drag and drop, *Design* for colours, font, corner radius, margins, header and footer.
- **Centre:** live preview with the latest finished check or demo data. Click a block in the preview to edit it.
- **Right:** the properties of the selected block: texts with placeholders (`{{firstname}}`, `{{domain}}`, `{{grade}}` …), rich text, images from the file manager or uploaded directly, alignment, spacing, background, what to show.
- **Templates:** "Load template" sets a ready-made layout (*Standard*, *With cover page*, *Compact*; e-mail: *Standard*, *Short*) in the language of the configuration. "PDF preview" opens the real PDF of the current draft.

**Every word is the customer's.** Apart from what the check finds (the AI reading, findings, area summaries, measures and measured values), everything in the e-mail and the PDF comes from the design: headings, labels, table columns, legend, grade words, area names, effort labels, the names of the measured values, header, footer and the word before the page number. Each block carries its words as properties; words used by several blocks are under *Design › Wording*. The templates arrive prefilled in the configuration's language (eleven languages included, see *Languages*; other languages through the AI translation below); the customer overwrites whatever they want to word differently.

**What the AI adds:** with an AI key the check reads up to five pages like a first-time visitor. Besides the content area it picks which marked screenshot excerpts the report shows and explains each in one sentence, and it may add up to two sections for topics outside the seven areas (block *AI sections*; it prints nothing when there is nothing to add). A check costs a few cents with a mid-range model.

**Advanced mode (administrators):** the switch *Visual | Advanced (Twig)* above the designer shows the complete document as Twig code: the frame with its CSS, every block's HTML and every word (in `wc_theme()` / `wc_block()` maps). Changes show in the live preview; syntax errors are shown there with the line and block saving. While code is stored it is used instead of the visual design; switching back to *Visual* discards the code after a confirmation.

**Languages the Contao way:** the content element has a **Configuration** field. *Automatic* uses the configuration whose language matches the page; or pick one explicitly (e.g. a second German configuration for a campaign). The check remembers its configuration, so the e-mail and PDF match the page the visitor used.

The PDF engine and the newsletter provider stay in `config.yaml` (technical, not editorial); API keys and the AI model are set in the settings (config.yaml as fallback). Non-admin groups need the module and the tables `tl_neo_websitecheck_settings` and `tl_neo_websitecheck_config`.

## Local test stand (Docker)

`dev/` holds a complete test environment: a fresh Contao Managed Edition with this bundle, MariaDB and [Mailpit](https://mailpit.axllent.org/) to look at every e-mail the check sends.

```bash
cd dev
cp .env.example .env        # optional: PageSpeed key, AI key, PDF engine
docker compose up --build   # first start installs Contao, takes a few minutes
```

- Back end: <http://localhost:8080/contao> — user `admin`, password from `.env` (default `admin12345`)
- Website with the check element: <http://localhost:8080/>
- Mailpit (visitor mail, your copy, PDF attachment): <http://localhost:8025>
- Chromium PDFs: `PDF_ENGINE=gotenberg` and `docker compose --profile gotenberg up`
- The first start also stores a **demo check** and sends its e-mails to Mailpit. Repeat any time, also on a real installation: `php vendor/bin/contao-console neo:websitecheck:demo --locale=de --send` (no API keys needed; `--email=` sends the report to you).

After changing PDF templates, `python3 dev/check-pdf-badges.py report.pdf` measures every grade badge (pypdfium2, numpy, scipy) and fails when a number is off-centre.

The repository is mounted into the container and symlinked by Composer, so template, PHP and translation changes are live (clear the cache with `docker compose exec app php vendor/bin/contao-console cache:clear`). `docker compose down -v` resets everything.

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

## Multilingual sites

Contao's way — one root page per language — works out of the box:

1. **One configuration per language** (*Website check → Configurations*, field *Language*). Each holds the e-mail and PDF design, the element texts, the **contact and privacy page of that language** and the **newsletter list, double-opt-in template and confirmation page** (live from Brevo).
2. **Place the element on each language's page** and pick that configuration (or leave it empty: the configuration whose language matches the page is used). A page whose language has no configuration gets the bundled template in its own language — never another language's configuration.
3. Everything follows the page language: the texts on the page, the check itself, the AI reading (written in the page language), e-mail, PDF and the newsletter sign-up. The 24-hour cache only reuses a check made in the same language.

Every link is the customer's choice (contact page, privacy page, newsletter confirmation page — in the settings or per configuration); the bundle never guesses a page of your site. Without a contact page the button is left out, without a confirmation page there is no newsletter box.

**Any language, none special-cased.** The configuration's language list offers every language Contao knows. Translation files ship for eleven languages (see *Languages*); for every other language the configured AI translates the visitor-facing texts (findings, measures, form, report, e-mail) once — automatically when a configuration in that language is saved, when a page in that language first shows the element (after the response) or at the latest before the first check is graded; it takes a few seconds and costs about a cent. `php bin/console neo:websitecheck:translate <lang> --export` writes them to `translations/neo_websitecheck.<lang>.yaml` for review — a project file always wins. Dates and numbers follow the language's own format (Intl). Without AI settings the installation's default language is used.

## Customising

- **Templates:** copy any file from `contao/templates/` of the bundle to `templates/` of your project and adapt it: the element (`content_element/neo_websitecheck.html.twig`, the AJAX flow stays intact as long as the `data-nwc-*` hooks remain), a designer block (`neo_websitecheck/blocks/pdf/cover.html.twig` …), the frames (`pdf_frame`, `mail_frame`) or the operator copy (`mail_owner`). Without a saved design, report and e-mail use the bundled *Standard* template in the check's language. Templates receive `result`, `areas`, `brand`, `images`, `crops`, `date`, `vars`, and blocks additionally `block` (its properties) and `t` (theme). To change only parts of the element, extend it (`{% extends "@Contao_NeoWebsitecheckBundle/content_element/neo_websitecheck.html.twig" %}`) and override its blocks: `nwc_intro`, `nwc_submit_button`, `nwc_gate_button`, `nwc_cta_buttons` (keep `type="submit"` and `data-nwc-reset`).
- **PDF badges:** use the `badge()` macro from `_pdf_macros.html.twig` for numbers in coloured boxes; Dompdf only centres text vertically with that structure.
- **Styles — neutral by default, designed by you.** Element, PDF and e-mail ship a neutral, brand-free base design. The element on the page takes **no colours from the configuration**; it is styled only by CSS custom properties, which you override in your theme (see *Styling the element*). PDF and e-mail take their colours from `brand.color_primary` / `brand.color_accent` (default: graphite `#1f2328` and blue `#0969da`) and are designed in the back-end designer.
- **Texts:** the words of e-mail and PDF are edited in the designer. `translations/neo_websitecheck.{en,de}.yaml` holds the prefilled template words, the texts of the check findings and the back end; override single keys in your project's `translations/` folder (for example to deliver findings in another language).
- **Grading:** `Check\Grader` holds the scoring; `Check\CheckRunner` the steps. Decorate or replace the services as usual in Symfony.

## Styling the element

`public/websitecheck.css` only sets a light, neutral base. Every colour, radius and font is a custom property on `.nwc`; override them in your stylesheet, for example a dark theme:

```css
.content-neo-websitecheck .nwc {
  --nwc-bg: #102a43; --nwc-bg-2: #163a5c;          /* panel (gradient from bg-2 to bg) */
  --nwc-fg: #fff; --nwc-muted: #c9d6e3;             /* text, secondary text (≥ 4.5:1 on bg) */
  --nwc-card: rgb(255 255 255 / .05); --nwc-border: rgb(255 255 255 / .14); --nwc-faint: rgb(255 255 255 / .14);
  --nwc-accent: #ffb000; --nwc-accent-ink: #102a43; /* buttons, progress, focus */
  --nwc-ring: rgb(255 176 0 / .25); --nwc-glow: rgb(255 176 0 / .45);
  --nwc-field-bg: rgb(0 0 0 / .3); --nwc-field-border: rgb(255 255 255 / .45); --nwc-placeholder: #9fb3c8;
  --nwc-err: #ffd1d4;                               /* error text and invalid field border */
  --nwc-good: #7bd88f; --nwc-warn: #ffb020; --nwc-bad: #ff6b73; --nwc-grade-ink: #102a43;
  --nwc-sent-bg: rgb(123 216 143 / .14); --nwc-sent-border: rgb(123 216 143 / .45);
  --nwc-failed-bg: rgb(255 107 115 / .14); --nwc-failed-border: rgb(255 107 115 / .5);
  --nwc-os-chrome: #0b1b2b; --nwc-os-ink: #c9d6e3; /* browser frame of the preview */
  --nwc-skel: #e6e9ed; --nwc-skel-hue: #c9d1d9;     /* placeholder page in the preview */
  --nwc-idle: rgb(11 27 43 / .66);                  /* overlay "Your website appears here" */
  --nwc-check-icon: url("data:image/svg+xml,…");    /* tick in finished steps, colour = accent-ink */
  --nwc-shadow: none; --nwc-os-shadow: 0 30px 60px -30px rgb(0 0 0 / .8);
  --nwc-radius: 22px; --nwc-radius-s: 12px;
  --nwc-font-display: inherit; --nwc-font-mono: ui-monospace, monospace;
}
```

Keep text at 4.5:1 and field borders at 3:1 against the panel. For a different layout, override the template `content_element/neo_websitecheck.html.twig` (keep the `data-nwc*` attributes the script uses).

## Analytics events

The JavaScript dispatches a DOM event `neo_websitecheck` on `document` (`event.detail = {name, params}`) and, with `tracking.data_layer: true`, pushes `{event: name, …params}` into `window.dataLayer`:

| Event | When | Parameters |
|---|---|---|
| `check_start` | address submitted | `domain`, `cached` |
| `check_gate` | result ready, lead form shown | `domain` |
| `check_abandon` | page left while the lead form was open | `domain` |
| `check_lead` | lead form submitted | `domain`, `newsletter` (0/1), `contact` (0/1) |
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
- **Umlauts missing in the PDF** – a custom template uses a font Dompdf does not have; keep Arimo or DejaVu Sans, load your own `.ttf` from the files directory, or switch to Gotenberg. Helvetica only knows Western European characters.
- **ALTCHA widget not visible / spam error** – ALTCHA needs HTTPS (or localhost); on Contao < 5.7 the honeypot is used automatically.

## Languages

Back end and default texts ship in German, English, French, Dutch, Italian, Polish, Czech, Hungarian, Slovenian, Croatian and Spanish (`contao/languages/<lang>/`, `translations/*.<lang>.yaml`). A project file `translations/<domain>.<lang>.yaml` overrides single texts. Contao itself offers Hungarian and Croatian as back-end language only when enabled in `config/config.yaml`:

```yaml
contao:
    intl:
        enabled_locales: ['+hu', '+hr']
```

## License

Commercial software, all rights reserved. Use on a public website requires a license from NEO Digital; testing on non-public development installations is allowed without one. Redistribution, resale and offering it as your own product or service are not permitted. Full terms: [LICENSE](LICENSE). Licensing: [neo-digital.at](https://neo-digital.at). The bundled Arimo font (`resources/fonts/arimo`) is licensed under the SIL Open Font License 1.1 (`OFL.txt`).

---

# NEO Website-Check für Contao 5 (Deutsch)

**Ein kostenloser Website-Check als Lead-Magnet.** Ein Besucher gibt seine Domain ein, das Bundle misst die Seite mit Google PageSpeed Insights, prüft HTML, Rechtliches und KI-Sichtbarkeit selbst, lässt optional eine KI die Seiten wie einen neuen Besucher lesen, benotet alles mit Schulnoten und schickt einen sechsseitigen PDF-Bericht per E-Mail – im Tausch gegen Vorname, Nachname und E-Mail-Adresse.

Entwickelt für eine produktive Contao-Website. Kommerzielle Lizenz, siehe [Lizenz](#lizenz).

## Was der Besucher sieht

1. **Adressfeld** – „Jetzt prüfen“. Spam-Schutz läuft unsichtbar (ALTCHA ab Contao 5.7, sonst Honeypot).
2. **Live-Fortschritt** – sechs Schritte mit Häkchen, Restzeit in Sekunden, Screenshot der geprüften Seite mit Markierungen an den Fundstellen. 60–120 Sekunden.
3. **Kontaktformular** – Vorname, Nachname, E-Mail (Pflicht), Newsletter-Häkchen (freiwillig, Double-Opt-in über das NEO-Brevo-Modul oder Contaos Newsletter-Modul). Ehrlich formuliert: ohne Daten kein Ergebnis.
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

- **Contao 5.3 LTS bis 5.7** (`contao/core-bundle ^5.3`, von Composer erzwungen, eine Installation unter Contao 4 ist damit ausgeschlossen) und **PHP 8.2+**. Getestet in einer frischen Contao-5.3-Managed-Edition und unter Contao 5.7.
- ALTCHA-Spamschutz ab Contao 5.7, davor Honeypot. GD mit JPEG und WebP (Standard).
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

Zuerst das mit der Lizenz erhaltene Paket-Repository in die `composer.json` eintragen:

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
- `newsletter.provider` – `auto` (Standard): mit installiertem NEO-Brevo-Modul Brevo (Schlüssel aus dem Modul), sonst Contaos Newsletter-Modul; `none` = kein Häkchen.
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
- PDF: `dompdf` (reines PHP; Standardschrift **Arimo**, mitgeliefert, sieht aus und läuft wie Arial; DejaVu und Helvetica unter *Design › Schrift*; **eigene Schriften und Bilder** lädt eine eigene Vorlage mit relativem Pfad aus dem Contao-Dateiordner, z. B. `@font-face { font-family: 'Marke'; src: url('files/fonts/Marke-Regular.ttf'); }` oder `<img src="files/bilder/siegel.png">` — statische `.ttf`-Dateien, kein WOFF2, keine variablen Schriften, Normal und Fett getrennt anmelden), `gotenberg` (Chromium-Container, `docker run --rm -p 3000:3000 gotenberg/gotenberg:8`) oder `none`.
- **Logos in E-Mails:** Mailprogramme zeigen kein SVG; neben ein SVG-Logo eine PNG-Datei gleichen Namens legen (`logo.svg` → `logo.png`, etwa 600 px breit) — E-Mails verwenden sie, das PDF bleibt beim SVG.
- Newsletter: kein Schlüssel in Konfigurationsdatei oder Umgebung. Mit installiertem NEO-Brevo-Modul läuft die Anmeldung über Brevo mit dem Schlüssel aus diesem Modul (Verteiler, Double-Opt-in-Vorlage und Bestätigungsseite je Konfiguration, live aus Brevo). Ohne Brevo-Modul über Contaos eigenes Newsletter-Modul: Verteiler und Bestätigungsseite (Seite mit dem Modul „Newsletter abonnieren“, dessen Text auch in der Bestätigungsmail steht). Angemeldet ist der Besucher erst nach dem Klick auf den Bestätigungslink.

## Backend-Einstellungen und der visuelle Designer

Alles, was ein Besucher sieht, wird im Backend unter **NEO › Website-Check Einstellungen** gepflegt, ohne `config.yaml` anzufassen (Backend-Werte gewinnen, leere Felder fallen auf die YAML zurück).

**Einstellungen (ein Datensatz):**

- **Wer den Bericht verschickt:** Firma/Agentur, Ansprechperson, Telefon, Website, Kontaktseite (Ziel der Knöpfe „Ergebnis besprechen“), Datenschutzseite.
- **Design:** Logo (PNG/JPG/SVG), Logo für dunklen Hintergrund, Porträtfoto, Haupt- und Akzentfarbe.
- **E-Mail:** Standard-Absender, Antwortadresse, Kopie an den Betreiber.
- **Grenzen:** Checks je Besucher und Stunde (Standard 2) und Tag (Standard 10), Zwischenspeicher, Aufbewahrung, Seiten je Check.

**Konfigurationen (eine je Sprache oder Einsatz):** „Konfigurationen bearbeiten“ öffnet die Liste. Jede Konfiguration hat einen Namen, eine Sprache und:

- **E-Mail:** Betreff, Absender, Antwortadresse und den **E-Mail-Designer**.
- **PDF-Bericht:** Dateiname mit Platzhaltern (`website-check-{{domain}}-{{date}}.pdf`) und den **PDF-Designer**.
- **Ihre Kopie:** Betreff der Kopie an den Betreiber.
- **Das Element auf der Seite:** Kurzfassung des Ergebnisses (Note, ein Satz, Hinweis aufs PDF; Standard) oder die ausführliche Übersicht, dazu die Beschriftungen von Formular, Kontaktformular und Ergebnis.
- **Interne Kopie (nur Administratoren):** optionale Twig-Vorlage für die Kopie an den Betreiber.

**Der Designer** (gleich für E-Mail und PDF) hat drei Bereiche:

- **Links:** *Blöcke* zum Anklicken oder Hineinziehen (Deckblatt, Überschrift, Text, Bild, Gesamtnote, Google-Ringe, Bereiche, Maßnahmen, Bereich im Detail, Tabelle der Seiten, So geht es weiter, Signatur, Seitenumbruch …), *Aufbau* zum Umsortieren per Drag & Drop, *Design* für Farben, Schrift, Rundung, Seitenränder, Kopf- und Fußzeile.
- **Mitte:** Live-Vorschau mit dem letzten fertigen Check oder Demo-Daten. Ein Klick auf einen Block in der Vorschau öffnet ihn zum Bearbeiten.
- **Rechts:** die Eigenschaften des gewählten Blocks: Texte mit Platzhaltern (`{{firstname}}`, `{{domain}}`, `{{grade}}` …), formatierter Text, Bilder aus der Dateiverwaltung oder direkt hochgeladen, Ausrichtung, Abstand, Hintergrund, was angezeigt wird.
- **Vorlagen:** „Vorlage laden“ setzt einen fertigen Aufbau (*Standard*, *Mit Deckblatt*, *Kompakt*; E-Mail: *Standard*, *Kurz*) in der Sprache der Konfiguration. „PDF-Vorschau“ öffnet das echte PDF des aktuellen Entwurfs.

**Jedes Wort gehört dem Kunden.** Außer dem, was der Check findet (KI-Lesung, Fundstellen, Zusammenfassungen der Bereiche, Maßnahmen und Messwerte), kommt alles in E-Mail und PDF aus der Gestaltung: Überschriften, Beschriftungen, Tabellenspalten, Legende, Notenwörter, Namen der Bereiche, Aufwand, Bezeichnungen der Messwerte, Kopf- und Fußzeile und das Wort vor der Seitenzahl. Jeder Block trägt seine Wörter als Eigenschaften; Wörter, die mehrere Blöcke nutzen, stehen unter *Design › Bezeichnungen*. Die Vorlagen kommen vorausgefüllt in der Sprache der Konfiguration (elf Sprachen liegen bei, siehe *Sprachen*; andere Sprachen über die KI-Übersetzung unten); was der Kunde anders formulieren will, überschreibt er.

**Was die KI beiträgt:** Mit KI-Schlüssel liest der Check bis zu fünf Seiten wie ein neuer Besucher. Neben dem Bereich Inhalte wählt sie aus, welche markierten Screenshot-Ausschnitte der Bericht zeigt, erklärt jeden in einem Satz und ergänzt bei Bedarf bis zu zwei eigene Abschnitte für Themen außerhalb der sieben Bereiche (Block *KI-Abschnitte*; bleibt leer, wenn es nichts zu ergänzen gibt). Ein Check kostet mit einem Mittelklasse-Modell wenige Cent.

**Erweiterter Modus (Administratoren):** Der Schalter *Visuell | Erweitert (Twig)* über dem Designer zeigt das komplette Dokument als Twig-Code: Rahmen mit CSS, das HTML jedes Blocks und jedes Wort (in `wc_theme()`- / `wc_block()`-Listen). Änderungen erscheinen in der Live-Vorschau; Syntaxfehler zeigt sie mit Zeilennummer an und verhindern das Speichern. Solange Code gespeichert ist, gilt er statt der visuellen Gestaltung; zurück auf *Visuell* verwirft den Code nach einer Rückfrage.

**Mehrsprachig nach Contao-Prinzip:** Das Inhaltselement hat ein Feld **Konfiguration**. *Automatisch* nimmt die Konfiguration mit der Sprache der Seite; oder man wählt ausdrücklich eine (z. B. eine zweite deutsche Konfiguration für eine Kampagne). Der Check merkt sich seine Konfiguration, damit E-Mail und PDF zur Seite passen, auf der der Besucher war.

API-Schlüssel, PDF-Engine und Newsletter-Anbieter bleiben in der `config.yaml` (technisch, nicht redaktionell). Nicht-Admin-Gruppen brauchen das Modul und die Tabellen `tl_neo_websitecheck_settings` und `tl_neo_websitecheck_config`.

## Lokaler Teststand (Docker)

`dev/` enthält eine komplette Testumgebung: frisches Contao Managed Edition mit diesem Bundle, MariaDB und [Mailpit](https://mailpit.axllent.org/), um jede E-Mail des Checks anzusehen.

```bash
cd dev
cp .env.example .env        # optional: PageSpeed-Schlüssel, KI-Schlüssel, PDF-Engine
docker compose up --build   # der erste Start installiert Contao, dauert einige Minuten
```

- Backend: <http://localhost:8080/contao> — Benutzer `admin`, Passwort aus `.env` (Standard `admin12345`)
- Website mit dem Check-Element: <http://localhost:8080/>
- Mailpit (Besucher-Mail, Ihre Kopie, PDF-Anhang): <http://localhost:8025>
- Chromium-PDFs: `PDF_ENGINE=gotenberg` und `docker compose --profile gotenberg up`
- Der erste Start legt außerdem einen **Demo-Check** an und schickt dessen E-Mails an Mailpit. Jederzeit wiederholbar, auch auf einer echten Installation: `php vendor/bin/contao-console neo:websitecheck:demo --locale=de --send` (keine API-Schlüssel nötig; `--email=` schickt den Bericht an Sie).

Nach Änderungen an PDF-Vorlagen misst `python3 dev/check-pdf-badges.py bericht.pdf` jedes Noten-Kästchen (pypdfium2, numpy, scipy) und schlägt fehl, wenn eine Ziffer nicht mittig sitzt.

Das Repository ist in den Container gemountet und per Composer verlinkt, Änderungen an Templates, PHP und Übersetzungen wirken sofort (Cache leeren: `docker compose exec app php vendor/bin/contao-console cache:clear`). `docker compose down -v` setzt alles zurück.

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

## Mehrsprachige Websites

So, wie Contao es vorsieht – ein Startpunkt je Sprache:

1. **Eine Konfiguration je Sprache** (*Website-Check → Konfigurationen*, Feld *Sprache*) mit E-Mail- und PDF-Gestaltung, Elementtexten, **Kontakt- und Datenschutzseite dieser Sprache** sowie **Newsletter-Verteiler, Double-Opt-in-Vorlage und Bestätigungsseite** (live aus Brevo).
2. **Element auf der Seite der jeweiligen Sprache** einsetzen und die Konfiguration wählen (leer = die Konfiguration mit der Sprache der Seite). Gibt es für eine Sprache keine Konfiguration, kommt die mitgelieferte Vorlage in dieser Sprache – nie die Konfiguration einer anderen Sprache.
3. Alles folgt der Seitensprache: Texte auf der Seite, KI-Lesung, E-Mail, PDF, Newsletter-Anmeldung. Der 24-Stunden-Zwischenspeicher verwendet nur einen Check derselben Sprache.

Alle Links wählt der Kunde selbst (Kontakt-, Datenschutz-, Newsletter-Bestätigungsseite – in den Einstellungen oder je Konfiguration); das Bundle rät nie eine Seite. Ohne Kontaktseite entfällt der Knopf, ohne Bestätigungsseite das Newsletter-Häkchen.

**Jede Sprache, keine als Sonderfall.** Die Sprachauswahl der Konfiguration bietet alle Sprachen, die Contao kennt. Sprachdateien liegen für elf Sprachen bei (siehe *Sprachen*); für jede andere Sprache übersetzt die eingestellte KI die Besucher-Texte (Befunde, Maßnahmen, Formular, Bericht, E-Mail) einmalig – automatisch beim Speichern einer Konfiguration in dieser Sprache, beim ersten Aufruf einer Seite dieser Sprache (nach dem Ausliefern) und spätestens vor der Benotung des ersten Checks; das dauert Sekunden und kostet rund einen Cent. `php bin/console neo:websitecheck:translate <sprache> --export` legt sie als `translations/neo_websitecheck.<sprache>.yaml` zum Korrigieren ab – eine Projektdatei hat immer Vorrang. Datum und Zahlen im Format der jeweiligen Sprache (Intl). Ohne KI-Einstellungen gilt die Standardsprache der Installation.

## Anpassen

- **Vorlagen:** Dateien aus `contao/templates/` des Bundles nach `templates/` im Projekt kopieren und anpassen: das Element (`content_element/neo_websitecheck.html.twig`, der AJAX-Ablauf bleibt erhalten, solange die `data-nwc-*`-Haken bleiben), einen Designer-Block (`neo_websitecheck/blocks/pdf/cover.html.twig` …), die Rahmen (`pdf_frame`, `mail_frame`) oder die Kopie an den Betreiber (`mail_owner`). Ohne gespeicherte Gestaltung nutzen Bericht und E-Mail die mitgelieferte Vorlage *Standard* in der Sprache des Checks. Um nur Teile des Elements zu ändern, es erweitern (`{% extends "@Contao_NeoWebsitecheckBundle/content_element/neo_websitecheck.html.twig" %}`) und seine Blöcke überschreiben: `nwc_intro`, `nwc_submit_button`, `nwc_gate_button`, `nwc_cta_buttons` (`type="submit"` und `data-nwc-reset` beibehalten).
- **Noten-Kästchen im PDF:** das Makro `badge()` aus `_pdf_macros.html.twig` verwenden; nur mit dieser Struktur zentriert Dompdf die Ziffer senkrecht.
- **Stile — neutral ausgeliefert, gestaltet beim Kunden.** Element, PDF und E-Mail kommen in einem neutralen Grunddesign ohne Marke. Das Element auf der Seite übernimmt **keine Farben aus der Konfiguration**, es wird nur über CSS-Variablen gestaltet (`--nwc-bg`, `--nwc-fg`, `--nwc-accent`, `--nwc-err`, … — vollständige Liste und Beispiel im englischen Teil unter *Styling the element*), die das Theme auf `.content-neo-websitecheck .nwc` überschreibt. PDF und E-Mail nehmen `brand.color_primary` / `brand.color_accent` (Standard: Graphit `#1f2328`, Blau `#0969da`) und werden im Designer gestaltet. Text ≥ 4,5:1, Feldränder ≥ 3:1 zur Fläche halten.
- **Texte:** Die Wörter von E-Mail und PDF bearbeitet man im Designer. `translations/neo_websitecheck.{en,de}.yaml` enthält die Vorbelegung der Vorlagen, die Texte der Check-Befunde und das Backend; einzelne Schlüssel im Projekt unter `translations/` überschreiben (z. B. für Befunde in einer weiteren Sprache).

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
- **Umlaute fehlen im PDF** – eigene Vorlage mit einer Schrift, die Dompdf nicht kennt; Arimo oder DejaVu Sans behalten, eine eigene `.ttf` aus dem Dateiordner laden oder Gotenberg verwenden. Helvetica kennt nur westeuropäische Zeichen.
- **ALTCHA nicht sichtbar / Spam-Fehler** – ALTCHA braucht HTTPS (oder localhost); unter Contao 5.7 greift automatisch der Honeypot.

## Sprachen

Backend und Standardtexte liegen in Deutsch, Englisch, Französisch, Niederländisch, Italienisch, Polnisch, Tschechisch, Ungarisch, Slowenisch, Kroatisch und Spanisch bei (`contao/languages/<sprache>/`, `translations/*.<sprache>.yaml`). Eine Datei `translations/<domain>.<sprache>.yaml` im Projekt überschreibt einzelne Texte. Ungarisch und Kroatisch bietet Contao als Backend-Sprache erst an, wenn sie in `config/config.yaml` freigeschaltet sind (`contao.intl.enabled_locales: ['+hu', '+hr']`).

## Lizenz

Kommerzielle Software, alle Rechte vorbehalten. Der Einsatz auf einer öffentlichen Website setzt eine Lizenz von NEO Digital voraus; zum Testen auf nicht öffentlichen Entwicklungsinstallationen ist keine nötig. Weitergabe, Weiterverkauf und das Anbieten als eigenes Produkt oder als Dienst sind nicht erlaubt. Vollständige Bedingungen: [LICENSE](LICENSE). Lizenzen: [neo-digital.at](https://neo-digital.at). Die mitgelieferte Schrift Arimo (`resources/fonts/arimo`) steht unter der SIL Open Font License 1.1 (`OFL.txt`).
