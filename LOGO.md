# Logo — cs-articles-module-maxxed

This extension has a brand logo. Two variants are checked into `media/`:

- **`media/logo.svg`** — duotone (cobalt #0102E1 newspaper, orange #FE9904 lightning-bolt badge in the top-right corner)
- **`media/logo-mono.svg`** — single-cobalt fallback for places where duotone won't reproduce

Both are SVG, scale to any size, transparent background.

## What this is

Newspaper icon (Tabler Icons `news`, MIT licensed) with a small orange lightning-bolt badge composited in the top-right corner. The newspaper reads "articles"; the bolt reads "boosted/maxxed" — so the icon as a whole says "articles module, supercharged." Bolt placement matches the corner-badge construction used elsewhere in the cs-* family for "the twist on the base icon."

Earlier draft used Tabler `article` (a rectangle with three full-width text lines) but it read too much like a hamburger menu. `news` has the distinctive folded-right newspaper tab so it's unambiguous.

This is the canonical logo for the cs-articles-module-maxxed extension. Use it on:

- The Joomla Extension Directory listing (when submitted)
- cybersalt.com sales / product page
- This repo's GitHub social preview image (optional — would need a 1280×640 PNG export)
- This README — at the top, to give the repo a brand face

## Source of truth

The canonical version lives in Tim's Obsidian vault at:

```
04.knowledge/cybersalt-com/branding/extension-logos/cs-articles-module-maxxed.svg
04.knowledge/cybersalt-com/branding/extension-logos/cs-articles-module-maxxed-mono.svg
```

If you ever need to update the logo, update it **in the vault first**, then re-copy to this repo's `media/` folder. The vault has the full family-wide convention, brand-color reference, render-check against multiple backgrounds, and the rationale for the design choices — see the README at:

```
04.knowledge/cybersalt-com/branding/extension-logos/README.md
```

## Brand colors

- Cybersalt cobalt: `#0102E1`
- Cybersalt orange: `#FE9904`

## TODO — wiring it into the package

These are the integration points to consider next time you're editing this extension. None are blocking for the logo simply existing in `media/`:

- [ ] **Joomla manifest** (`csarticlesmodulemaxxed.xml`): this is a system plugin, so to make the logo install with the plugin, add a `<media>` element to the manifest:
  ```xml
  <media folder="media" destination="plg_system_csarticlesmodulemaxxed">
      <filename>logo.svg</filename>
      <filename>logo-mono.svg</filename>
  </media>
  ```
  After install, the logo will be at `JPATH_ROOT/media/plg_system_csarticlesmodulemaxxed/logo.svg`.
- [ ] **README.md**: add the logo at the top:
  ```markdown
  <img src="media/logo.svg" width="128" alt="cs-articles-module-maxxed logo">
  ```
- [ ] **JED listing**: upload `logo.svg` as the extension icon when submitting / updating the JED listing.

Logo added: 2026-05-06.
