# Changelog

All notable changes to this bundle are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
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
