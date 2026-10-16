# Changelog

All notable changes to this bundle are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Changed
- **License changed from MIT to a commercial license** (see `LICENSE`). Use on a public website requires a license from NEO Digital; testing on non-public installations stays free. Copies obtained earlier under MIT keep that license.
- **Language-independent:** no language is special-cased any more. The configuration offers every language Contao knows; languages are matched by their primary subtag; three-letter codes and regional variants (`fil`, `de-CH`, `zh-Hant`) are accepted everywhere (`locale` column widened to 32 characters); dates and numbers use the language's own format (Intl); quotation marks come from the catalogue; the AI reading has no German special case. For languages without translation file the configured AI translates the visitor-facing texts once (AiCatalogue, served by a translator decorator for this bundle's domain only); new console command `neo:websitecheck:translate <lang> [--force] [--export]`.

### Added
- **Own fonts and images in the PDF (Dompdf):** custom templates may load TrueType fonts and images from the Contao files directory with a relative path (`url('files/…/Brand.ttf')`, `<img src="files/…">`). The files directory is added to Dompdf's allowed paths; nothing else outside the storage directory is readable.

### Added
- **Button blocks in the element template:** `nwc_submit_button`, `nwc_gate_button` and `nwc_cta_buttons`, so a site can use its own button markup (e.g. with icons) without copying the whole template. The extra reset button after a failed check is now a copy of the result's reset button and keeps that markup.

### Changed
- **Marked excerpts share one format:** every excerpt is cut at 5 : 2 (height = width × 0.4), centred on the marked element, so excerpts line up in the report and on the result page. The padding gives way first; only an element taller than the frame makes an excerpt taller. Applies to new checks.

### Fixed
- **Progress list without a running step:** while a step was being worked on (for example by the cron job in parallel to the visitor's page), the status showed no step as running during the first step and the already finished step as running later on. The stored step is the last finished one; the step after it is now the running one.
- **Empty first screen for pages with full-height sections:** Lighthouse takes its full-page screenshot by stretching the window to the page height, so a hero sized `100vh` grows to thousands of pixels and the first screen of that capture shows only background. Such captures are now detected (elements of the first screen sit far from their measured position) and the first screen, the live preview, the AI reading and the markers use Google's end-of-load screenshot and the positions measured during the run instead. Applies to new checks.
- **Broken image in the live preview:** the preview image is shown only after it has loaded; a failed load is retried with the next status update.
- **Markers and excerpts in the wrong place:** Lighthouse measures the audit nodes at another moment than the full-page screenshot (it stretches the viewport for the capture, so full-height sections move and overlays such as cookie banners close). Markers and excerpts now use the screenshot's own node map; elements not visible on the image get no marker. Applies to new checks.
- **No logo or images in e-mails (Outlook, Gmail …):** images were embedded as `data:` URIs, which most mail programs drop silently. E-mails now carry them as inline attachments (`cid:`). Mail programs show no SVG either: for e-mails an SVG logo is replaced by a PNG with the same name next to it (`logo.svg` → `logo.png`); without one the company name is shown. The PDF keeps the SVG.
- **Second check without reloading failed the spam check:** an ALTCHA solution is accepted once, but "Check another website" sent the used one again. The widget now solves a fresh challenge after every start request.
- **Designer missing after "Save":** Contao's back end swaps the page with Turbo after saving, so the designer never started and only the plain Twig field was left. It now also starts on `turbo:load`, each designer independently; if one fails, it shows the cause in place instead of an empty area. Invalid UTF-8 in a text no longer empties the designer's configuration.
- **PDF designer in the dark back-end theme:** the designer follows Contao's dark mode (`html[data-color-scheme="dark"]`); buttons, lists and fields no longer show light text on white. In both themes every text reaches at least 4.5:1 and every control border 3:1 (WCAG 2.1 AA); all colours are `--nwd-*` variables.

### Added
- **Budget guard against automated use:** limits for new checks of all visitors together (*Settings → Limits*, default 20 per hour / 60 per day; cached results do not count). Requests from scripts and headless browsers (empty user agent, HeadlessChrome, curl, python …) are refused like a failed spam check — on top of the invisible ALTCHA proof-of-work and the honeypot.
- **API keys and AI model in the back end:** *Settings → Keys and AI* holds the PageSpeed key, the AI key (both stored encrypted with the app secret, never shown again), the AI model (a searchable list of the provider's models as soon as a key is known) and the provider address. config.yaml / environment stay the fallback for empty fields; a status line shows where each key comes from. A migration takes existing keys, model and address over from the configuration once (empty fields only).
- **One-time contact permission:** optional box in the lead form ("You may contact me once by e-mail about this result", text `fe.contact`, switch *Ask for contact permission* per configuration, on by default). Stored per check (`contact`, wording and time in `data.consent`), shown in the operator copy, sent as `contact` (0/1) with the `check_lead` event. The privacy line under the form now reads "… and for what you explicitly tick above".

### Changed
- **Newsletter without a key in config or environment:** with the NEO Brevo module installed the check uses Brevo with the API key stored in that module; otherwise Contao's own newsletter module (channel + confirmation page with the *Subscribe* module, Contao's double opt-in and e-mail text). `newsletter.provider` is now `auto` | `brevo` | `contao` | `none` (default `auto`); `newsletter.brevo_api_key` is deprecated and ignored. The back-end fields follow the system in use (Brevo list/template or Contao channel).
- **Live preview shows the desktop page:** the browser window of the element now shows the desktop first screen (was the mobile one, cropped), and its markers come from the desktop run; the mobile screenshot stays the fallback and the PDF report is unchanged.
- Template block `nwc_intro` around the intro text, so a site can place eyebrow, heading and intro inside the panel in its template override.
- **Eleven languages:** back end, designer and all default texts (findings, report, e-mail, form) ship in German, English, French, Dutch, Italian, Polish, Czech, Hungarian, Slovenian, Croatian and Spanish; these languages need no AI translation. The check recognises imprint, privacy, terms, contact and opening hours in these languages and postal codes of their formats.
- **Visual designer for e-mail and PDF** replaces the long list of text fields: blocks (cover, heading, text, image, grade, rings, areas, measures, area detail, pages table, next steps, signature, page break …) added by click or drag and drop, reordered in the structure, edited in a properties panel with placeholders, rich text and image upload; theme settings (colours, font, radius, margins, header/footer); live preview; ready-made templates per language; real PDF of the draft. Stored as JSON and rendered through one Twig partial per block (overridable).
- **Configurations instead of text records:** `tl_neo_websitecheck_config` (one per language or use case) holds subject, sender, PDF file name, both designs, the element texts and the expert Twig templates. The content element picks a configuration or uses the one of the page language; the check stores it (`config_id`).

- **Every word in e-mail and PDF comes from the design.** Labels that were fixed translations (grade line, meta labels, table columns, legend, "what works", grade words, area names, effort, names of measured values, header, footer, page label, sources, PDF badge) are now block properties or *Design › Wording* fields, prefilled in the configuration's language. Only the check results (AI reading, findings, summaries, measures, values) come from the check. The plain-text part of the e-mail is derived from the designed HTML.
- **Advanced mode:** the switch *Visual | Advanced (Twig)* (administrators) shows the whole document as Twig code (frame, CSS, block HTML, all words via `wc_theme()`/`wc_block()`), with live preview, error display with line number and validation on save. The generated code renders identically to the visual design. Replaces "Copy to Twig template" and the separate expert fields for e-mail and PDF.
- Without a saved design, report and e-mail render the bundled *Standard* template; the old file templates `report.html.twig` and `mail_visitor.html.twig` were removed.

- **The AI prepares the report:** it receives the marked screenshot excerpts cut from Google's measurement (now up to eight candidates), picks the two or three that matter most for the business and writes a plain caption for each. It may add one or two sections of its own for topics the seven areas do not cover (prices, booking, trust, service area, emergency service …); the new PDF block **AI sections** renders them and is part of all PDF templates. Default `ai.max_tokens` raised to 3000.

- **Short result on the page (default):** after the lead form the element shows only the grade with one sentence, the note that the report is on its way and the call to action; the PDF carries everything else. The full overview (scores, areas, measures, strengths) stays available per configuration ("Result on the page").
- **Rate limit per hour:** a visitor (IP) may start two checks per hour by default (`limits.checks_per_ip_per_hour`, back end "Checks per visitor and hour"), with its own message; the day limit default is now 10.

- **Multilingual sites (one configuration per language):** each configuration now holds the contact and privacy page of its language (form, e-mail, PDF) and the newsletter list, double-opt-in template and confirmation page (live from Brevo, number fields without an API key); empty fields fall back to the global values. The newsletter box only shows when that language can really be signed up.
- **Neutral base design:** the element ships a light, brand-free look and is styled only through `--nwc-*` custom properties (all colours, including field borders, errors, preview frame, skeleton, tick icon); the controller no longer writes the brand colours inline, so a theme can restyle it without `!important`. Default brand colours for PDF and e-mail are now graphite `#1f2328` and blue `#0969da` (were dark violet and lime). README: *Styling the element*.
- **PDF font Arimo (looks like Arial):** bundled (SIL OFL 1.1) and the new default under *Design › Font*; it covers Latin, Greek and Cyrillic, so names and AI text with "ł", "ő" or "→" print correctly (Dompdf's built-in Helvetica, which "Arial" maps to, only knows Western European characters). DejaVu stays selectable; grade badges are centred per font.

### Fixed
- **No guessed links:** every link (contact, privacy, newsletter confirmation page) is the customer's choice; the bundle no longer falls back to the start page for "Discuss the result" in e-mail/PDF, nor to the start page or `example.com` as newsletter confirmation page. Without a chosen page the button is left out, and without a confirmation page the newsletter box is not shown.
- **Multilingual:** the 24-hour cache reused a check made in another language (summaries, measures and AI reading came in the wrong language); it now only reuses checks of the same language. A page whose language had no configuration got the first configuration of any language; it now gets the bundled template in its own language. The AI reading was written in German or English only; it now uses the page language.
- **Privacy:** a check served from the 24-hour cache copied the source check's folder including its PDF, and that PDF (with the first visitor's name) was sent again. The copy now skips PDFs, and a PDF is only reused when it belongs to the same check.
- **Numbers in grade badges sat 2–6 mm too high in Dompdf PDFs.** Dompdf only centres text vertically when width, height and `vertical-align: middle` are on the table cell; every badge now uses one measured macro (`_pdf_macros.html.twig`). An automatic check measured all badges in all templates (offset ≤ 0.3 mm).
- PDF and e-mail labels used the language of the current request (English in the console or for browsers set to English); rendering now switches the translator to the check's language.
- Default report: the second column of area cards ran past the right page margin; measure rows showed a seam between number and text; legend chips were off-centre.
- Google rings in the PDF were missing (Dompdf ignores inline SVG): the ring is now an SVG image with the number centred on top.
- The *Standard* template printed "At a glance" twice; areas of a grade-less area showed "Grade – · –".
- Screenshots in the report were Google's thumbnails (250 px mobile, 500 px desktop). The first screen is now cut from the full-page capture in full resolution (412 px / 1350 px).
- The idle overlay text in the preview window ("Your website appears here") had 4.3:1 contrast; the overlay is darker now (6.4:1).
- The ALTCHA widget no longer shows a visible box in the form; it still verifies in the background.
- Marked excerpts skipped nothing: icon-font glyphs ("cleaning_services", "search") were cut out and listed as examples. They are now left out, at most two excerpts per audit are taken and overlapping excerpts are skipped.
- The cover repeated the logo already shown in the header line.
- **Google measured only the loading speed:** the four categories were sent as `category[0]=…`, which PageSpeed ignores, so accessibility, best practices and SEO came back empty and were graded 5. The parameter is now repeated as Google expects. A category that is missing anyway counts as "not measured", never as 0.
- Marked excerpts were limited to the first 5000 px of the page; findings further down were skipped. The full page is now stored (up to 20 000 px).
- The accessibility line quoted our stricter internal score as "Lighthouse … von 100"; it now shows Google's value.
- Raw technical error messages (e.g. Google's quota message in English) no longer appear in the visitor's report; they stay in the back end.
- The page text read by the AI contained inline scripts, styles and icon-font ligatures; only visible text is sent now.
- Loading speed said "faster than most websites" with grade 1 while the main content needed 3.6 s (high Google score). Google's own LCP limits now cap grade and wording: over 2.5 s at most grade 2, over 4 s at most grade 4.
- A start page that could not be fetched still produced a report (empty domain, grade 5, AI call, e-mail). The check now stops with the "could not be checked" message; no report, no e-mail, no AI cost.
- Slow servers: pages may take up to 35 s (was 10–20 s); a tested start page needed 21 s and failed the check.
- The public status API passed the internal error text (server paths, host names) to the browser; it now only reports that the check failed.
- PDF layout: area details, strengths and AI sections are now rows of card pairs. A whole area used to be one table row, which Dompdf cannot split, so long areas jumped to the next page and left nearly empty pages. Headings stay with their first row.
- Findings without tailored wording showed Lighthouse's Markdown code marks; they are removed, and "zoom blocked" (meta-viewport) has its own plain wording.
- A second claim of the same check could subscribe a different address to the newsletter; the address of the first claim is used now.
- Logo and portrait were embedded at full size in every e-mail and PDF (a camera portrait made a 2.2 MB PDF); they are downscaled now (0.6 MB). New config key `brand.logo_dark` for the e-mail header.

### Fixed
- JSON endpoints (`start`, `claim`) answered 400 for visitors with any cookie: Contao's request-token check applied to them. Routes now set `_token_check: false` and the script sends `X-Requested-With`.
- Contao 5.3: templates were not found (missing `.twig-root` marker); content element used the non-existent `jumpTo`/`guests` columns — now the core `url` field with page picker.
- Breadcrumb shows "Edit settings" / "Language DE" instead of "Edit element ID 1"; texts link keeps the parent id.

### Added
- Docker test stand in `dev/` (Contao + MariaDB + Mailpit) and `neo:websitecheck:demo` (finished demo check with PDF and e-mails, no API keys).
- Back-end module **Website check settings**: brand, design (logo, portrait, colours), sender addresses, limits, and the customer-facing texts per language with placeholders; "Preview PDF" and "Preview e-mail" with the newest check or demo data.
- Complete e-mail, owner-copy and PDF templates per language (HTML/Twig, prefilled, administrators only), per-language sender and reply-to, PDF file name with placeholders, language-specific previews.

## [1.0.0] – 2026-10-10

### Added
- Content element **Website check** (`neo_websitecheck`): URL form → live progress with remaining seconds, step list and screenshot preview → lead form (first name, last name, e-mail, optional newsletter) → graded result on the page.
- Resumable check engine that runs inside the visitor's status polls (no worker or cron required), optionally driven by `neo:websitecheck:run` or the Contao cron.
- Measurements: Google PageSpeed Insights (mobile + desktop, up to three pages), own HTML analysis (titles, headings, images, language, viewport, structured data, links, contact signals), legal check (imprint, privacy policy, consent tool, third-party services before consent), AI-visibility check (`llms.txt`, `llms-full.txt`, AI crawlers in `robots.txt`, identity schema), mobile check.
- Optional AI reading of the page texts and the start-page screenshot via any OpenAI-compatible endpoint (Requesty, OpenAI, Azure, Ollama …) with a fixed JSON contract.
- School grades (1–5) for seven areas and an overall grade that is never better than the worst area plus one; three prioritised measures with effort; strengths.
- Six-page A4 PDF report (Dompdf by default, Gotenberg optional) with screenshots and marked accessibility excerpts; e-mail to the visitor with the PDF attached; operator copy with the lead data.
- Rate limit per IP and day, 24-hour result cache per domain, ALTCHA (Contao ≥ 5.7) or honeypot spam protection, token-protected endpoints, SSRF guard for private hosts.
- Back-end list of checks with details, re-send and delete; daily purge after the retention period.
- Brevo double-opt-in integration for the newsletter checkbox.
- `dataLayer` events and a DOM event (`neo_websitecheck`) for analytics.
- English and German translations for front end, report and e-mails.
