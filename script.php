<?php
/**
 * @package     Cybersalt.Plugin
 * @subpackage  System.csarticlesmodulemaxxed
 *
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

return new class () implements InstallerScriptInterface {
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if (!\in_array($type, ['install', 'update', 'discover_install'], true)) {
            return true;
        }

        if ($type !== 'update') {
            $this->enablePlugin();
        }

        $this->renderInstallCard($type);

        return true;
    }

    private function enablePlugin(): void
    {
        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->createQuery()
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('csarticlesmodulemaxxed'));
            $db->setQuery($query)->execute();
        } catch (\Throwable $e) {
            // Non-fatal — user can enable manually from the plugin manager.
        }
    }

    private function renderInstallCard(string $type): void
    {
        // Per Joomla-Brain JOOMLA-EXTENSION-WISHLIST.md §"Post-Install Card With Next Steps"
        // (locked-in v2026-05-06 spec): logo at 56px left of title, description paragraph
        // below, action buttons in Cybersalt orange #dc6b1a, footer with vendor + support,
        // CSS-variable theme switching for light/dark Atum.

        $e = static fn (string $key): string => htmlspecialchars(
            Text::_($key),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $title = $type === 'update'
            ? $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_TITLE_UPDATE')
            : $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_TITLE_INSTALL');

        // The XML description is what the plugin manager listing shows; same string here
        // so the user immediately recognises what they just installed.
        $description = $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_XML_DESCRIPTION');
        $linkPlugin  = $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_LINK_PLUGIN');
        $linkModules = $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_LINK_MODULES');
        $support     = $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_SUPPORT');

        $pluginUrl  = 'index.php?option=com_plugins&filter[folder]=system&filter[element]=csarticlesmodulemaxxed';
        $modulesUrl = 'index.php?option=com_modules';
        $vendorUrl  = 'https://www.cybersalt.com';
        $supportUrl = 'https://github.com/cybersalt/cs-articles-module-maxxed/issues';
        $logoUrl    = Uri::root() . 'media/plg_system_csarticlesmodulemaxxed/logo.svg';

        echo <<<HTML
<style>
/* Light mode defaults */
.cs-install-card {
    --cs-header-bg: #fff;
    --cs-header-title: #0102E1;        /* Cybersalt cobalt */
    --cs-header-border: rgba(0, 0, 0, 0.1);
}
/* Dark mode — Joomla 5/6 uses BOTH selectors depending on template/version */
html[data-bs-theme="dark"] .cs-install-card,
html[data-color-scheme="dark"] .cs-install-card {
    --cs-header-bg: #1f2937;           /* slate-800 */
    --cs-header-title: #FE9904;        /* Cybersalt brand orange */
    --cs-header-border: rgba(255, 255, 255, 0.1);
}

.cs-install-card .cs-card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    background-color: var(--cs-header-bg);
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid var(--cs-header-border);
}
.cs-install-card .cs-card-header img {
    height: 56px;
    width: 56px;
    flex: 0 0 56px;
}
/* Dark mode — the cobalt logo loses contrast against the slate header so
 * wrap it in a white disc (same trick the cs-siteground-cache header uses). */
html[data-bs-theme="dark"] .cs-install-card .cs-card-header img,
html[data-color-scheme="dark"] .cs-install-card .cs-card-header img {
    background-color: #fff;
    border-radius: 50%;
    padding: 6px;
    box-sizing: content-box;
}
.cs-install-card .cs-card-header h3 {
    margin: 0;
    font-size: 1.4rem;
    color: var(--cs-header-title);
}

/* Action buttons in Cybersalt orange — white text reads in either mode */
.cs-install-card a.cs-cybersalt-btn,
.cs-install-card a.cs-cybersalt-btn:link,
.cs-install-card a.cs-cybersalt-btn:visited {
    background-color: #dc6b1a;
    border-color: #dc6b1a;
    color: #fff !important;
}
.cs-install-card a.cs-cybersalt-btn:hover,
.cs-install-card a.cs-cybersalt-btn:focus,
.cs-install-card a.cs-cybersalt-btn:active {
    background-color: #b85614;
    border-color: #b85614;
    color: #fff !important;
}
</style>
<div class="card my-3 cs-install-card">
    <div class="card-header cs-card-header">
        <img src="{$logoUrl}" alt="" />
        <h3>{$title}</h3>
    </div>
    <div class="card-body">
        <p class="lead mb-3">{$description}</p>
        <p class="mb-0">
            <a class="btn btn-sm cs-cybersalt-btn" href="{$pluginUrl}">{$linkPlugin}</a>
            <a class="btn btn-sm cs-cybersalt-btn ms-2" href="{$modulesUrl}">{$linkModules}</a>
        </p>
        <hr>
        <p class="text-muted small mb-0">
            {$support}
            &middot;
            <a href="{$vendorUrl}" target="_blank" rel="noopener noreferrer">Cybersalt</a>
            &middot;
            <a href="{$supportUrl}" target="_blank" rel="noopener noreferrer">Report a bug</a>
        </p>
    </div>
</div>
HTML;
    }
};
