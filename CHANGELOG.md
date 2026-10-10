# Changelog

All notable changes to this bundle are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Changed
- **Visual designer for e-mail and PDF** replaces the long list of text fields: blocks (cover, heading, text, image, grade, rings, areas, measures, area detail, pages table, next steps, signature, page break …) added by click or drag and drop, reordered in the structure, edited in a properties panel with placeholders, rich text and image upload; theme settings (colours, font, radius, margins, header/footer); live preview; ready-made templates per language; real PDF of the draft. Stored as JSON and rendered through one Twig partial per block (overridable).
- **Configurations instead of text records:** `tl_neo_websitecheck_config` (one per language or use case) holds subject, sender, PDF file name, both designs, the element texts and the expert Twig templates. The content element picks a configuration or uses the one of the page language; the check stores it (`config_id`).

- **Every word in e-mail and PDF comes from the design.** Labels that were fixed translations (grade line, meta labels, table columns, legend, "what works", grade words, area names, effort, names of measured values, header, footer, page label, sources, PDF badge) are now block properties or *Design › Wording* fields, prefilled in the configuration's language. Only the check results (AI reading, findings, summaries, measures, values) come from the check. The plain-text part of the e-mail is derived from the designed HTML.
- **Advanced mode:** the switch *Visual | Advanced (Twig)* (administrators) shows the whole document as Twig code (frame, CSS, block HTML, all words via `wc_theme()`/`wc_block()`), with live preview, error display with line number and validation on save. The generated code renders identically to the visual design. Replaces "Copy to Twig template" and the separate expert fields for e-mail and PDF.
- Without a saved design, report and e-mail render the bundled *Standard* template; the old file templates `report.html.twig` and `mail_visitor.html.twig` were removed.

### Fixed
- **Privacy:** a check served from the 24-hour cache copied the source check's folder including its PDF, and that PDF (with the first visitor's name) was sent again. The copy now skips PDFs, and a PDF is only reused when it belongs to the same check.
- **Numbers in grade badges sat 2–6 mm too high in Dompdf PDFs.** Dompdf only centres text vertically when width, height and `vertical-align: middle` are on the table cell; every badge now uses one measured macro (`_pdf_macros.html.twig`). An automatic check measured all badges in all templates (offset ≤ 0.3 mm).
- PDF and e-mail labels used the language of the current request (English in the console or for browsers set to English); rendering now switches the translator to the check's language.
- Default report: the second column of area cards ran past the right page margin; measure rows showed a seam between number and text; legend chips were off-centre.
- Google rings in the PDF were missing (Dompdf ignores inline SVG): the ring is now an SVG image with the number centred on top.
- The *Standard* template printed "At a glance" twice; areas of a grade-less area showed "Grade – · –".

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
