<img src="media/logo.svg" width="128" alt="cs-articles-module-maxxed logo" align="left" hspace="16">

# Cybersalt Articles Module Maxxed

A Joomla 5 / Joomla 6 system plugin that adds a **"Skip first N articles"** setting to the core articles modules.

## Why

Joomla's core articles modules only ever start at the most recent article. There's no way to say *"skip the first 5 — they're already on screen above me."*

This plugin adds that setting, so a module can be configured as a blog-tease *"more articles"* row that picks up where the page's main article list left off.

## How it works

`plg_system_csarticlesmodulemaxxed` listens on three Joomla events:

1. **`onContentPrepareForm`** — injects a *Skip first N articles* field into the target module's settings panel (in the existing **Module** tab, no new tab).
2. **`onAfterModuleList`** — for any target module instance with `Skip > 0`, bumps the module's `count` parameter by `Skip` so the SQL query fetches enough rows.
3. **`onAfterRenderModule`** — strips the first `Skip` `<li>` items from the rendered HTML using DOMDocument, leaving only the "next batch" visible.

The strip step targets `<ul>` / `<ol>` elements whose class contains `mod-list` or `mod-articles-items` (the wrappers used by core layouts) so it can't accidentally strip a list that appears inside an article's introtext.

## Supported modules

| Module | Joomla 5 | Joomla 6 |
|---|:---:|:---:|
| `mod_articles` (the unified module, added in J5.2) | ✅ 5.2+ | ✅ |
| `mod_articles_category` (legacy) | ✅ | ✅ |
| `mod_articles_latest` (legacy) | ✅ | ✅ |

The plugin's own settings include a checkbox group so you can disable the field per module type if you want.

## Limitations

The render-strip step removes the first *N* `<li>` children from the first articles-list wrapper in the rendered output. That works for the default Joomla layouts and for most template overrides that keep a `<ul>...<li>` structure. **Template overrides that use a different structure (a series of `<div>`s, a CSS grid of `<article>`s, etc.) will not be sliced.** In that case the module will simply show all `count + skip` articles.

If you hit this on a real site, [open an issue](https://github.com/cybersalt/cs-articles-module-maxxed/issues) — a configurable item selector can be added in a future release.

## Requirements

- Joomla 5.0+ or Joomla 6.0+
- PHP 8.1+

## Installation

1. Download the latest `plg_system_csarticlesmodulemaxxed_*.zip` from the [Releases page](https://github.com/cybersalt/cs-articles-module-maxxed/releases).
2. In Joomla admin: **System → Install → Extensions → Upload Package File**.
3. The plugin auto-enables on first install. (If you ever disable it: **System → Plugins → search "Articles Module Maxxed"** → set **Status** to **Enabled**.)
4. Open any Articles module — you'll see a new **Skip first N articles** field next to **Number of Articles**.

After installation, Joomla's Update Manager automatically polls this repo's [updates.xml](https://raw.githubusercontent.com/cybersalt/cs-articles-module-maxxed/main/updates.xml) and offers updates as they're released.

## Configuration

In the module settings:

- **Skip first N articles** — number of leading articles to drop. Set to `0` to disable.

In the plugin settings (**System → Plugins → Cybersalt - System - Articles Module Maxxed**):

- **Apply to modules** — toggle which core module types expose the new field.
- **Support email / URL / label** — used in any "contact support" message the extension surfaces. Defaults to Cybersalt support; resellers can override.

## Example: blog-tease "More articles" row

You have a category-blog menu item showing the **5 most recent** articles up top. You want a *"More articles"* module below it showing **articles 6 through 10** with thumbnails and titles.

1. Add an **Articles** module (J6 `mod_articles`) to a position below the blog.
2. Set **Number of Articles** = `5`.
3. Set **Skip first N articles** = `5`.
4. Set the same category, ordering, etc. as the blog menu item.

Result: the module's SQL fetches 10 articles, then the first 5 are stripped from the rendered HTML — leaving exactly articles 6–10 on screen.

## Build

```powershell
& 'C:\Program Files\7-Zip\7z.exe' a -tzip plg_system_csarticlesmodulemaxxed_v{version}.zip csarticlesmodulemaxxed.xml script.php services\ src\ forms\ language\
```

Build artifacts live in the repo root and are excluded from git via `.gitignore` (`*.zip`).

CI runs PHP lint (8.1 + 8.3), manifest XML validation, language-file UTF-8/no-BOM check, and zip-build smoke-test on every push and pull request — see [.github/workflows/ci.yml](.github/workflows/ci.yml).

## Security

Reviewed pre-publish against Cybersalt's security checklist: zero HIGH, zero MEDIUM findings. The plugin reads no untrusted input into SQL/filesystem/eval; the one SQL statement (postflight auto-enable) uses fully parameterised values; the DOMDocument strip step runs on PHP 8+ where external entity loading is disabled by default.

## Languages

Ships with translation stubs for all 15 Cybersalt-target languages: en-GB, nl-NL, de-DE, es-ES, fr-FR, it-IT, pt-BR, ru-RU, pl-PL, ja-JP, zh-CN, tr-TR, el-GR, cs-CZ, sv-SE. Only en-GB is translated; the others mirror en-GB and are marked **TRANSLATION PENDING** in the file header. PRs welcome.

## Credits

This extension was vibe-coded live on stream with the help of:

- **[Bjørn Ove Bremnes](https://github.com/) — the original "wouldn't it be nice if this was in core" spark.** Bjørn wished an articles offset was available natively in Joomla's core articles module; this plugin is that wish, productionised.
- **[The Basic Joomla Tutorials channel family](https://www.youtube.com/@basicjoomla)** — for hosting and helping vibe-code this during a live stream.
- **Tim Davis ([Cybersalt](https://cybersalt.com))** — author / maintainer.

## License

GNU General Public License version 2 or later. See https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

## Author

[Cybersalt Consulting Ltd.](https://cybersalt.com) — support@cybersalt.com

## Issues / feature requests

https://github.com/cybersalt/cs-articles-module-maxxed/issues
