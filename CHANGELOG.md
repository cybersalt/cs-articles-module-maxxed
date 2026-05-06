# Changelog

All notable changes to **Cybersalt Articles Module Maxxed** are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-05-06

### 🚀 New

- Initial release.
- System plugin that adds a **Skip first N articles** setting to Joomla 6's unified `mod_articles` plus the legacy `mod_articles_category` and `mod_articles_latest` modules.
- Per-module-type opt-out in the plugin's own settings (*Apply to modules* checkbox group).
- Count-bump on `onAfterModuleList` so the SQL fetches enough rows.
- DOMDocument-based render-strip on `onAfterRenderModule` so the first N items are removed from the visible output, scoped by the `mod-list` / `mod-articles-items` class selector so it never strips a list inside an article's introtext.
- Postflight install card pointing the user to the plugin settings + modules manager.
- Auto-enable on first install.
- Configurable Support contact (email / URL / label) for "contact support" messages.
- Update server broadcast — Joomla's Update Manager will detect new versions automatically.
- 15-language coverage: en-GB translated; nl-NL, de-DE, es-ES, fr-FR, it-IT, pt-BR, ru-RU, pl-PL, ja-JP, zh-CN, tr-TR, el-GR, cs-CZ, sv-SE shipped as `TRANSLATION PENDING` stubs (PRs welcome).
- GitHub Actions CI: PHP lint (8.1 + 8.3), manifest XML validation, language-file UTF-8/no-BOM check, zip-build smoke-test on every push.

### 🔐 Security

- Pre-publish security review passed with zero HIGH / zero MEDIUM findings.
- One LOW (HTML markup in install-card language strings, theoretical XSS neutered by `htmlspecialchars` but cosmetically broken) fixed before tagging.

### 📦 Compatibility

- Joomla 5.0+ and Joomla 6.0+ native (no `Behaviour - Backward Compatibility` plugin required).
- PHP 8.1+.

### 🙌 Credits

- **Bjørn Ove Bremnes** — for wishing an articles offset was a feature in Joomla's core articles module. This plugin is that wish, productionised.
- **The Basic Joomla Tutorials channel family** — for hosting and helping vibe-code this during a live stream.
- **Tim Davis ([Cybersalt](https://cybersalt.com))** — author / maintainer.
