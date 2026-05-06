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
        $e = static fn (string $key): string => htmlspecialchars(
            Text::_($key),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $title    = $type === 'update'
            ? $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_TITLE_UPDATE')
            : $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_TITLE_INSTALL');
        $intro    = $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_INTRO');
        $stepsH   = $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_STEPS_HEADING');
        $step1    = $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_STEP_1');
        $step2    = $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_STEP_2');
        $step3    = $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_STEP_3');
        $linkPlug = $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_LINK_PLUGIN');
        $linkMods = $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_LINK_MODULES');
        $support  = $e('PLG_SYSTEM_CSARTICLESMODULEMAXXED_INSTALL_CARD_SUPPORT');

        $pluginUrl = 'index.php?option=com_plugins&filter[folder]=system&filter[element]=csarticlesmodulemaxxed';
        $modulesUrl = 'index.php?option=com_modules';

        echo <<<HTML
<div class="card my-3 cs-install-card">
    <div class="card-header bg-primary text-white">
        <h3 class="m-0">{$title}</h3>
    </div>
    <div class="card-body">
        <p class="lead">{$intro}</p>
        <h4>{$stepsH}</h4>
        <ol>
            <li>{$step1} <a class="btn btn-sm btn-outline-primary ms-2" href="{$pluginUrl}">{$linkPlug}</a></li>
            <li>{$step2}</li>
            <li>{$step3} <a class="btn btn-sm btn-outline-primary ms-2" href="{$modulesUrl}">{$linkMods}</a></li>
        </ol>
        <hr>
        <p class="text-muted small mb-0">{$support} &mdash; <a href="https://cybersalt.com">Cybersalt</a> &middot; support@cybersalt.com</p>
    </div>
</div>
HTML;
    }
};
