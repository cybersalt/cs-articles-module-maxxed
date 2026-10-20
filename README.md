<img src="media/logo.svg" width="128" alt="Cybersalt Articles Module Maxxed logo" align="left" hspace="16">

# Cybersalt Articles Module Maxxed

A Joomla 5 / Joomla 6 system plugin that adds a **"Skip first N articles"** setting to Joomla's core articles modules.

- **Documentation:** <https://docs.cybersalt.com/extensions/articles-module-maxxed>
- **Download and product page:** <https://www.cybersalt.com/extensions/articles-module-maxxed>
- **Support:** [Cybersalt support request form](https://www.cybersalt.com/services/support-request-form) or [GitHub issues](https://github.com/cybersalt/cs-articles-module-maxxed/issues)

<br clear="left">

## Why

Joomla's core articles modules always start at the most recent article. There's no setting for *"skip the first 5, they're already on screen above me."*

This plugin adds that setting, so a module can be a *"More articles"* row that picks up where the page's main article list left off.

## Example: a "More stories" row

A blog or featured page shows the **6 newest** articles. Below it you want a module showing **articles 7 to 12**.

1. Add an **Articles** module below the blog.
2. Set **Articles to Display** = `6`.
3. Set **Skip first N articles** = `6` (the field sits right under the article count).
4. Use the same categories and ordering as the blog.

The module fetches 12 articles and drops the first 6 from what it shows, leaving articles 7 to 12.

## Supported modules

| Module | Joomla 5 | Joomla 6 |
|---|:---:|:---:|
| `mod_articles` (Articles, the unified module added in J5.2) | ✅ 5.2+ | ✅ |
| `mod_articles_category` (Articles - Category) | ✅ | ✅ |
| `mod_articles_latest` (Articles - Latest) | ✅ | ✅ |

The plugin's settings let you switch the field off for module types you don't use.

## How it works

`plg_system_csarticlesmodulemaxxed` listens on three Joomla events:

1. **`onContentPrepareForm`** adds the *Skip first N articles* field to the module form, directly after the module's own article count.
2. **`onAfterModuleList`** raises the module's `count` by the skip value, so the query fetches enough articles. (A count of `0`, meaning "all" in Articles - Category, is left alone.)
3. **`onAfterRenderModule`** removes the first N articles from the rendered list.

The last step works on the lists the core layouts use (`<ul>`/`<ol>` with the class `mod-list` or `mod-articles-items`), so it never touches a list inside an article's intro text. With grouping turned on it counts articles across all groups, in the order the module shows them, and removes a group that ends up empty. If nothing is left after skipping, the module is not shown.

## Limitations

- **Template overrides with different markup.** If an override replaces the list with a series of `<div>`s or a grid of `<article>`s, the plugin can't find the list and the module shows all `count + skip` articles. (If the override keeps a plain `<ul>`/`<ol>`, the first list with more than N items is trimmed.) [Open an issue](https://github.com/cybersalt/cs-articles-module-maxxed/issues) if you need a configurable selector.
- **Grouping by category, author or tag.** The skip follows the order the groups are displayed, which may differ from the date order of the page's main list. Grouping by date in the same order as the ordering lines up exactly.

## Requirements

- Joomla 5.0+ or 6.0+ (the Articles module itself needs Joomla 5.2+)
- PHP 8.1+

## Installation

1. Download the zip from [cybersalt.com](https://www.cybersalt.com/extensions/articles-module-maxxed) (or the [GitHub releases](https://github.com/cybersalt/cs-articles-module-maxxed/releases)).
2. In Joomla admin: **System → Install → Extensions → Upload Package File**.
3. The plugin enables itself on first install.
4. Open any Articles module: the **Skip first N articles** field is under the article count.

Updates come through Joomla's Update Manager from Cybersalt's update server.

## Configuration

Module setting:

- **Skip first N articles**: how many leading articles to drop. `0` turns it off for that module.

Plugin settings (**System → Plugins → System - Cybersalt Articles Module Maxxed**):

- **Apply to modules**: which of the three module types get the field.
- **Email Super Users when Joomla is updated**: sends one email per Joomla version change, reminding Super Users to check the plugin still works. Default Yes.
- **Support email / URL / label**: shown in that email. Defaults to Cybersalt support; resellers can change them.

Full details: [documentation](https://docs.cybersalt.com/extensions/articles-module-maxxed).

## Build

```powershell
& 'C:\Program Files\7-Zip\7z.exe' a -tzip plg_system_csarticlesmodulemaxxed_v{version}.zip csarticlesmodulemaxxed.xml script.php services\ src\ forms\ language\ media\ CHANGELOG.md CHANGELOG.html
```

Zips are git-ignored. CI runs PHP lint (8.1 + 8.3), manifest XML validation, a language-file check and a zip-build smoke test on every push and pull request ([ci.yml](.github/workflows/ci.yml)).

## Languages

17 languages: en-GB, nl-NL, de-DE, es-ES, fr-FR, it-IT, pt-BR, ru-RU, pl-PL, ja-JP, zh-CN, tr-TR, el-GR, cs-CZ, sv-SE, nb-NO, nn-NO. Only en-GB is written by a native speaker; the others are machine translations. Corrections are welcome as pull requests.

## Credits

- **Bjørn Ove Bremnes**, whose wish for an articles offset in Joomla core started this.
- **[The Basic Joomla Tutorials channel family](https://www.youtube.com/@basicjoomla)**, where it was built live on stream.
- **Tim Davis ([Cybersalt](https://www.cybersalt.com))**, author and maintainer.

## License

GNU General Public License version 2 or later. See [LICENSE](LICENSE) and [NOTICE](NOTICE) (includes the Tabler Icons MIT attribution for the logo artwork).

## Author

[Cybersalt Consulting Ltd.](https://www.cybersalt.com)
