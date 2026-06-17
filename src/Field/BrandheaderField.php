<?php
/**
 * @package     Cybersalt.Plugin
 * @subpackage  System.csarticlesmodulemaxxed
 *
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Cybersalt\Plugin\System\Csarticlesmodulemaxxed\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Renders a Cybersalt brand element inside a plugin-settings fieldset.
 *
 * Two variants are supported via the XML attribute `variant`:
 *
 *  - `variant="page-info-logo"` — emits no visible row. Instead it injects a
 *    small JS snippet that prepends the extension logo to Joomla's plugin
 *    manager `<h2>` plugin-name heading (the one rendered above the Basic
 *    fieldset by `com_plugins/tmpl/plugin/edit.php`). Use this on the
 *    Basic fieldset so the logo appears next to the plugin name without
 *    duplicating the title in a second card.
 *
 *  - default (no variant) — renders a full brand-header card: logo + plugin
 *    name + per-tab subtitle. Use this on later fieldsets (e.g. Support)
 *    where Joomla doesn't render its own plugin-info block at the top.
 *
 * Accepts the XML attribute `subtitle="LANG_KEY"` for the per-tab subtitle in
 * the default variant.
 */
final class BrandheaderField extends FormField
{
    protected $type = 'Brandheader';

    public function renderField($options = [])
    {
        if ($this->hidden) {
            return '';
        }

        $variant = (string) ($this->element['variant'] ?? '');

        if ($variant === 'page-info-logo') {
            return $this->renderPageInfoLogoInjection();
        }

        return $this->renderFullBrandHeader();
    }

    protected function getInput()
    {
        return '';
    }

    /**
     * Inject the extension logo into Joomla's plugin manager `<h2>` plugin
     * name heading via a small inline script. The script is idempotent (uses
     * a `data-cs-logo-injected` flag) so it stays safe if the form re-renders.
     *
     * Returns a hidden wrapper so no visible row appears in the fieldset.
     */
    private function renderPageInfoLogoInjection(): string
    {
        $logoUrl = htmlspecialchars(
            Uri::root() . 'media/plg_system_csarticlesmodulemaxxed/logo.svg',
            ENT_QUOTES,
            'UTF-8'
        );

        return <<<HTML
<style>
/* Inject-variant logo styling. Sized via CSS so the JS stays minimal.
 * In dark mode, the cobalt artwork loses contrast against Atum's dark
 * surface, so wrap it in a white disc — same approach the cs-siteground-cache
 * header uses for the same problem. Padding sits OUTSIDE the 44px image
 * (box-sizing: content-box) so the disc is visibly larger than the logo. */
.cs-page-info-logo {
    height: 44px;
    width: 44px;
    flex: 0 0 44px;
    margin-right: 0.75rem;
}
html[data-bs-theme="dark"] .cs-page-info-logo,
html[data-color-scheme="dark"] .cs-page-info-logo {
    background-color: #fff;
    border-radius: 50%;
    padding: 6px;
    box-sizing: content-box;
}
</style>
<div class="cs-brandheader-injection" style="display:none;" aria-hidden="true">
<script>
(function () {
    var logoUrl = "{$logoUrl}";

    var run = function () {
        // The com_plugins edit template renders the plugin name as a single
        // <h2> in .col-lg-9. Scope to that column so we don't accidentally
        // match an unrelated h2 from a global-fields panel.
        var h2 = document.querySelector('.main-card .col-lg-9 > h2, .col-lg-9 > h2');

        if (!h2 || h2.dataset.csLogoInjected) {
            return;
        }

        var img = document.createElement('img');
        img.src = logoUrl;
        img.alt = '';
        img.className = 'cs-page-info-logo';

        h2.style.display = 'flex';
        h2.style.alignItems = 'center';
        h2.insertBefore(img, h2.firstChild);
        h2.dataset.csLogoInjected = '1';
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
</script>
</div>
HTML;
    }

    /**
     * Render the full branded header card: logo + plugin name + subtitle.
     * Used on fieldsets that don't have a Joomla-rendered info block at the
     * top (the Support fieldset, primarily).
     */
    private function renderFullBrandHeader(): string
    {
        $logoUrl = htmlspecialchars(
            Uri::root() . 'media/plg_system_csarticlesmodulemaxxed/logo.svg',
            ENT_QUOTES,
            'UTF-8'
        );

        $title = htmlspecialchars(
            Text::_('PLG_SYSTEM_CSARTICLESMODULEMAXXED'),
            ENT_QUOTES,
            'UTF-8'
        );

        $subtitleKey = (string) ($this->element['subtitle'] ?? '');
        $subtitle    = $subtitleKey !== ''
            ? htmlspecialchars(Text::_($subtitleKey), ENT_QUOTES, 'UTF-8')
            : '';

        $subtitleHtml = $subtitle !== ''
            ? '<small>' . $subtitle . '</small>'
            : '';

        return <<<HTML
<style>
/* Layout-only — text colours inherit from Joomla's body so the header looks
 * native in both light and dark Atum without us hard-coding brand colours
 * that would clash with the surrounding admin chrome. The card background
 * does follow a subtle light/dark swap so the header visually groups itself. */
.cs-plugin-tab-header {
    --cs-tab-header-bg: #fff;
    --cs-tab-header-border: rgba(0, 0, 0, 0.1);
    --cs-tab-header-subtitle: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 0 0 1.5rem;
    padding: 0.75rem 1rem;
    border: 1px solid var(--cs-tab-header-border);
    border-radius: 0.375rem;
    background-color: var(--cs-tab-header-bg);
}
html[data-bs-theme="dark"] .cs-plugin-tab-header,
html[data-color-scheme="dark"] .cs-plugin-tab-header {
    --cs-tab-header-bg: #1f2937;
    --cs-tab-header-border: rgba(255, 255, 255, 0.1);
    --cs-tab-header-subtitle: rgba(255, 255, 255, 0.6);
}
.cs-plugin-tab-header img {
    height: 48px;
    width: 48px;
    flex: 0 0 48px;
}
/* Dark mode — the cobalt logo loses contrast against the slate card so
 * wrap it in a white disc (same trick the cs-siteground-cache header uses). */
html[data-bs-theme="dark"] .cs-plugin-tab-header img,
html[data-color-scheme="dark"] .cs-plugin-tab-header img {
    background-color: #fff;
    border-radius: 50%;
    padding: 6px;
    box-sizing: content-box;
}
.cs-plugin-tab-header-text h4 {
    margin: 0;
    font-size: 1.15rem;
    line-height: 1.2;
    color: inherit;
}
.cs-plugin-tab-header-text small {
    display: block;
    margin-top: 0.15rem;
    color: var(--cs-tab-header-subtitle);
    font-size: 0.85rem;
}
</style>
<div class="cs-plugin-tab-header">
    <img src="{$logoUrl}" alt="" />
    <div class="cs-plugin-tab-header-text">
        <h4>{$title}</h4>
        {$subtitleHtml}
    </div>
</div>
HTML;
    }
}
