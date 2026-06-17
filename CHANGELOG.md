# Changelog

All notable changes to **Cybersalt Articles Module Maxxed** are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.1] - 2026-06-17

### 🐞 Fix — Regular Labs Advanced Module Manager compatibility

- **Skip field now appears under AMM.** Regular Labs Advanced Module Manager (AMM) entirely replaces Joomla's core `com_modules` with `com_advancedmodules` — its module edit form is named `com_advancedmodules.module` rather than `com_modules.module`. The plugin's `onContentPrepareForm` handler was doing a strict equality match on `com_modules.module`, so the Skip field never appeared on sites using AMM. The check is now `str_ends_with($formName, '.module')`, which accepts both Joomla core's and AMM's module edit forms. The existing `isTargetEnabled()` whitelist still confines injection to the three supported articles modules, so the looser form-name match doesn't open the field up to other unrelated forms.

### 📦 Compatibility

- Joomla 5.0+ and Joomla 6.0+ native.
- Compatible with Regular Labs Advanced Module Manager (any version that fires `onContentPrepareForm` from the module edit form).
- PHP 8.1+.

## [1.2.0] - 2026-06-17

### 🐞 Fix — Joomla 6 compatibility

- **Skip field now appears on the article module edit form on Joomla 6.** The plugin's `onContentPrepareForm`, `onAfterModuleList`, and `onAfterRenderModule` handlers were reading event arguments via the legacy generic-event API (`$event->getArguments()['subject']` etc.). On Joomla 6 the dispatcher delivers these as concrete typed events (`PrepareFormEvent`, `AfterModuleListEvent`, `AfterRenderModuleEvent`), and reaching into the underlying arguments array was unreliable. Handlers now use the typed-event getter methods (`getForm()`, `getData()`, `getModules()`, `getModule()`) with a generic-event fallback for any rare legacy caller.
- **`$autoloadLanguage = true`.** Joomla only auto-loads a plugin's `.sys.ini` globally; the `.ini` only loaded inside the plugin's own settings page on J5 and not at all reliably on J6. With this flag set, the plugin's `.ini` is loaded automatically every time the plugin's events fire, so the Skip field's label and description render correctly on the module edit form on both J5 and J6.
- **Defensive try/catch around all three module-form/render handlers.** A throwable in the plugin can no longer break the module edit form or a frontend page render — failures are logged to `plg_system_csarticlesmodulemaxxed` and the handler bails out silently.

### 📦 Compatibility

- Joomla 5.0+ and Joomla 6.0+ native — confirmed against the typed-event API in both versions.
- PHP 8.1+.

## [1.1.1] - 2026-05-06

### 🔐 Security / Hardening

- **Defence-in-depth on the params writer.** If `#__extensions.params` is ever non-empty but contains invalid JSON (only possible via direct DB tampering — Joomla itself never produces this state), `writeLastSeenJoomlaVersion()` now logs a warning and bails out instead of overwriting the corrupted-but-real settings blob with a minimal `{"last_seen_joomla":"..."}` object. Empty params row → still safely treated as `[]` and written as before. Identified as INFO-2 in the v1.1.0 pre-announcement security review.

## [1.1.0] - 2026-05-06

### 🚀 New

- **Joomla-version-change watch** — when Joomla itself is upgraded on the site, the plugin sends a one-time email to every active Super User reminding them to verify the plugin still works on the new Joomla version. The email includes what to check, where to grab a newer plugin version (GitHub releases), how to file an issue (GitHub issues), and how to contact the configured support address. Steady-state cost is one in-memory string compare per admin request; DB+email work runs only on the rare moment Joomla actually changes versions.
- **Notify opt-out** — *Email Super Users when Joomla is updated* toggle in the plugin's basic settings (default Yes).
- **17-language coverage** — translations now ship for the full Cybersalt-target language list: en-GB (native), nl-NL, de-DE, es-ES, fr-FR, it-IT, pt-BR, ru-RU, pl-PL, ja-JP, zh-CN, tr-TR, el-GR, cs-CZ, sv-SE, **nb-NO** (new), **nn-NO** (new). Non-English files are AI-translated with native-speaker review welcome via PR.

### 🌍 Norwegian

- Added Norwegian Bokmål (nb-NO) and Nynorsk (nn-NO) — partly a Cybersalt-customer language, partly a tribute to **Bjørn Ove Bremnes**, the Norwegian whose original "wouldn't it be nice if Joomla's articles module had this" wish became this plugin.

### 🔧 Improvements

- Provider now wires `DatabaseInterface` into the plugin so the new version-watch can query Super Users without going through `Factory::getDbo()`.

### 📦 Compatibility

- Joomla 5.0+ and Joomla 6.0+ native (no Backward-Compat plugin required).
- PHP 8.1+.

### 🙌 Credits

- **Bjørn Ove Bremnes** — for wishing an articles offset was a feature in Joomla's core articles module. This plugin (and the Norwegian translations in this release) is that wish, productionised.
- **The Basic Joomla Tutorials channel family** — for hosting and helping vibe-code this during a live stream.
- **Tim Davis ([Cybersalt](https://cybersalt.com))** — author / maintainer.

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
