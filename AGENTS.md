# Carouselfy — Agent Guidelines & Architecture Rules

## Architecture Overview
- **Runtime Environment:** Hostinger Shared Hosting (Apache 2.4+ / PHP 8.3 / MySQL 5.7+ / MariaDB 10.3+ via PDO).
- **Client Studio:** Production React & Tailwind CSS vector canvas compiled to `assets/js/studio.js` and `assets/css/studio.css` via `npm run build:client`.
- **Slide Archetype System:** Slides are constructed from structured `SlideData` models (`src/lib/archetypes.ts`) stored on `Slide.data`; element edits sync back bidirectionally via `dataKey`. Templates re-render any slide dynamically without losing text or structure.
- **AI Content Engine:** Backend LLM integration in `includes/ai.php` (`POST /api/ai/generate.php`) supporting Groq (`openai/gpt-oss-120b`), OpenAI (`gpt-4o-mini`), Google Gemini (`gemini-1.5-flash`), Anthropic Claude, and OpenRouter, backed by a domain-adaptive topic engine for offline reliability.

## Production Deployment & Hosting Constraints
- **Hostinger Compatibility:** All PHP code must run without Node.js at runtime. Static JS/CSS bundles must reside in `assets/` and `public_html/assets/`.
- **Non-Destructive Database Operations:** Strictly execute `CREATE TABLE IF NOT EXISTS` or non-destructive column checks. Never run `DROP`, `TRUNCATE`, `DELETE`, or destructive `ALTER` commands against production database `u561630513_Insta_auto`.
- **Secret Zero-Exposure:** Never commit database credentials, API tokens, or `.env` files to git. All credentials are read from `.env` outside version control.
- **Dual Root Path Parity:** Maintain file synchronization between root and `public_html/` (`config/`, `includes/`, `assets/`, `api/`) to support both document root configurations.
