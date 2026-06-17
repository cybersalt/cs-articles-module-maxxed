<?php
/**
 * @package     Cybersalt.Plugin
 * @subpackage  System.csarticlesmodulemaxxed
 *
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Cybersalt\Plugin\System\Csarticlesmodulemaxxed\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\Module\AfterModuleListEvent;
use Joomla\CMS\Event\Module\AfterRenderModuleEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

final class Csarticlesmodulemaxxed extends CMSPlugin implements SubscriberInterface, DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    // Force-load the plugin's own .ini at every event firing — without this,
    // Joomla only auto-loads the .sys.ini globally, and the Skip field's label
    // and description render as raw language constants on the module edit form.
    protected $autoloadLanguage = true;

    private const PARAM_KEY = 'cs_skip_articles';

    private const PLUGIN_VERSION = '1.2.1';

    private const SUPPORTED_MODULES = [
        'mod_articles',
        'mod_articles_category',
        'mod_articles_latest',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareForm' => 'onContentPrepareForm',
            'onAfterModuleList'    => 'onAfterModuleList',
            'onAfterRenderModule'  => 'onAfterRenderModule',
            'onAfterInitialise'    => 'onAfterInitialise',
        ];
    }

    /**
     * Watches for Joomla core version changes and (one time per change) emails
     * every Super User a heads-up reminding them to verify this plugin still
     * works on the new Joomla version.
     *
     * Runs admin-only and short-circuits in nanoseconds when the stored
     * version matches the running version, so steady-state cost is zero.
     */
    public function onAfterInitialise(Event $event): void
    {
        $app = $this->getApplication();

        if ($app === null || !$app->isClient('administrator')) {
            return;
        }

        if ((int) $this->params->get('notify_on_joomla_update', 1) !== 1) {
            return;
        }

        $current = (new Version())->getShortVersion();
        $stored  = trim((string) $this->params->get('last_seen_joomla', ''));

        if ($stored === $current) {
            return;
        }

        try {
            // First run after install/update of the plugin: no baseline yet.
            // Establish silently — don't alert on the first observation.
            if ($stored === '') {
                $this->writeLastSeenJoomlaVersion($current);
                return;
            }

            // Atomic-ish: re-read DB before write to reduce duplicate-email
            // races when multiple admin requests hit at the same moment.
            $reReadStored = $this->readLastSeenJoomlaVersionFromDb();

            if ($reReadStored === $current) {
                // Another concurrent request already handled it.
                $this->params->set('last_seen_joomla', $current);
                return;
            }

            $this->writeLastSeenJoomlaVersion($current);
            $this->notifyJoomlaUpdate($reReadStored ?: $stored, $current);
        } catch (\Throwable $e) {
            Log::add(
                'csarticlesmodulemaxxed: Joomla-update notification failed: ' . $e->getMessage(),
                Log::WARNING,
                'plg_system_csarticlesmodulemaxxed'
            );
        }
    }

    public function onContentPrepareForm(Event $event): void
    {
        try {
            // Prefer J5/J6 typed-event getters; fall back to argument-array
            // access for any rare caller that dispatches a generic Event.
            $form = $event instanceof PrepareFormEvent
                ? $event->getForm()
                : $this->resolveArg($event, 'subject') ?? $this->resolveArg($event, 'form');

            // Accept any module edit form, not just Joomla core's
            // `com_modules.module`. Regular Labs Advanced Module Manager
            // entirely replaces com_modules with com_advancedmodules and
            // names its form `com_advancedmodules.module`; without this
            // looser check the Skip field never appeared on sites using
            // AMM. The isTargetEnabled() check below still confines the
            // injection to our three supported articles modules.
            if (!$form instanceof Form || !str_ends_with($form->getName(), '.module')) {
                return;
            }

            $data = $event instanceof PrepareFormEvent
                ? $event->getData()
                : $this->resolveArg($event, 'data');

            $element = $this->extractModuleElement($data);

            if ($element === '' || !$this->isTargetEnabled($element)) {
                return;
            }

            Form::addFormPath(\dirname(__DIR__, 2) . '/forms');
            $form->loadFile('skip-articles-field', false);
        } catch (\Throwable $e) {
            // Never break the module edit form because of this plugin —
            // log and bail out so the admin form still renders.
            Log::add(
                'csarticlesmodulemaxxed: onContentPrepareForm failed: ' . $e->getMessage(),
                Log::WARNING,
                'plg_system_csarticlesmodulemaxxed'
            );
        }
    }

    public function onAfterModuleList(Event $event): void
    {
        try {
            $modules = $event instanceof AfterModuleListEvent
                ? $event->getModules()
                : $this->resolveArg($event, 'modules');

            if (!\is_array($modules)) {
                return;
            }

            foreach ($modules as $module) {
                if (!\is_object($module)) {
                    continue;
                }

                $element = $module->module ?? '';

                if (!\is_string($element) || !$this->isTargetEnabled($element)) {
                    continue;
                }

                $params = $this->decodeParams($module->params ?? '');
                $skip   = (int) ($params[self::PARAM_KEY] ?? 0);

                if ($skip <= 0) {
                    continue;
                }

                $count = (int) ($params['count'] ?? 0);

                // count = 0 means "all" in mod_articles_category and friends — leave it alone.
                if ($count > 0) {
                    $params['count'] = $count + $skip;
                    $module->params  = json_encode($params);
                }
            }
        } catch (\Throwable $e) {
            Log::add(
                'csarticlesmodulemaxxed: onAfterModuleList failed: ' . $e->getMessage(),
                Log::WARNING,
                'plg_system_csarticlesmodulemaxxed'
            );
        }
    }

    public function onAfterRenderModule(Event $event): void
    {
        try {
            $module = $event instanceof AfterRenderModuleEvent
                ? $event->getModule()
                : $this->resolveArg($event, 'subject');

            if (!\is_object($module)) {
                return;
            }

            $element = $module->module ?? '';

            if (!\is_string($element) || !$this->isTargetEnabled($element)) {
                return;
            }

            $params = $this->decodeParams($module->params ?? '');
            $skip   = (int) ($params[self::PARAM_KEY] ?? 0);

            if ($skip <= 0 || empty($module->content)) {
                return;
            }

            $module->content = $this->stripFirstNListItems($module->content, $skip);
        } catch (\Throwable $e) {
            Log::add(
                'csarticlesmodulemaxxed: onAfterRenderModule failed: ' . $e->getMessage(),
                Log::WARNING,
                'plg_system_csarticlesmodulemaxxed'
            );
        }
    }

    /**
     * Remove the first $n direct <li> children from the first <ul>/<ol> in $html
     * that has more than $n list-item children. Falls back to returning $html
     * unchanged if no suitable list is found.
     */
    private function stripFirstNListItems(string $html, int $n): string
    {
        if ($n <= 0 || trim($html) === '') {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);

        $doc     = new \DOMDocument('1.0', 'UTF-8');
        $wrapper = '<div id="csarmm-wrap">' . $html . '</div>';
        $loaded  = $doc->loadHTML(
            '<?xml encoding="UTF-8"?>' . $wrapper,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return $html;
        }

        $xpath = new \DOMXPath($doc);
        $wrap  = $xpath->query('//div[@id="csarmm-wrap"]')->item(0);

        if (!$wrap instanceof \DOMElement) {
            return $html;
        }

        // Prefer the articles-module list wrapper (class contains "mod-list" or
        // "mod-articles-items") so we never accidentally strip a list that
        // appears inside an article's introtext. Fall back to any <ul>/<ol>
        // only if no class-marked list is found.
        $primaryQuery = './/ul[contains(concat(" ", normalize-space(@class), " "), " mod-list ")]'
            . ' | .//ol[contains(concat(" ", normalize-space(@class), " "), " mod-list ")]'
            . ' | .//ul[contains(concat(" ", normalize-space(@class), " "), " mod-articles-items ")]';

        $lists = $xpath->query($primaryQuery, $wrap);

        if ($lists->length === 0) {
            $lists = $xpath->query('.//ul | .//ol', $wrap);
        }

        $stripped = false;

        foreach ($lists as $list) {
            $items = [];

            foreach ($list->childNodes as $child) {
                if (
                    $child->nodeType === XML_ELEMENT_NODE
                    && strtolower($child->nodeName) === 'li'
                ) {
                    $items[] = $child;
                }
            }

            if (\count($items) > $n) {
                for ($i = 0; $i < $n; $i++) {
                    $list->removeChild($items[$i]);
                }

                $stripped = true;
                break;
            }
        }

        if (!$stripped) {
            return $html;
        }

        $inner = '';
        foreach ($wrap->childNodes as $child) {
            $inner .= $doc->saveHTML($child);
        }

        return $inner;
    }

    private function resolveArg(Event $event, string $name): mixed
    {
        // Fallback for generic Events (legacy triggerEvent callers). J5/J6
        // typed events are handled via their getter methods directly in the
        // event handlers above.
        $args = $event->getArguments();

        return $args[$name] ?? null;
    }

    private function extractModuleElement(mixed $data): string
    {
        // Edit-form load: $data is the loaded item record and carries ->module.
        if (\is_object($data) && !empty($data->module)) {
            return (string) $data->module;
        }

        if (\is_array($data) && !empty($data['module'])) {
            return (string) $data['module'];
        }

        // Save POST: FormModel::loadForm calls preprocessForm with an empty
        // $data, so we can't see the module element from the event. Fall back
        // to the request body (jform[module] is a hidden field on the form).
        $app = $this->getApplication();

        if ($app !== null) {
            $jform = $app->getInput()->get('jform', [], 'array');

            if (!empty($jform['module'])) {
                return (string) $jform['module'];
            }
        }

        return '';
    }

    private function isTargetEnabled(string $element): bool
    {
        if (!\in_array($element, self::SUPPORTED_MODULES, true)) {
            return false;
        }

        $configured = $this->params->get('targets', \implode(',', self::SUPPORTED_MODULES));

        if (\is_array($configured)) {
            $allowed = $configured;
        } else {
            $allowed = \array_filter(\array_map('trim', \explode(',', (string) $configured)));
        }

        return \in_array($element, $allowed, true);
    }

    private function decodeParams(string $json): array
    {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return \is_array($decoded) ? $decoded : [];
    }

    private function readLastSeenJoomlaVersionFromDb(): string
    {
        $db = $this->getDatabase();

        $query = $db->createQuery()
            ->select($db->quoteName('params'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('csarticlesmodulemaxxed'));

        $paramsJson = (string) $db->setQuery($query)->loadResult();
        $params     = $this->decodeParams($paramsJson);

        return trim((string) ($params['last_seen_joomla'] ?? ''));
    }

    private function writeLastSeenJoomlaVersion(string $version): void
    {
        $db = $this->getDatabase();

        $query = $db->createQuery()
            ->select($db->quoteName('params'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('csarticlesmodulemaxxed'));

        $paramsJson = (string) $db->setQuery($query)->loadResult();

        // Defence-in-depth: if the row exists but its params are corrupted
        // (non-empty but invalid JSON) we'd otherwise overwrite a real
        // settings blob with our minimal {last_seen_joomla:...} object,
        // losing every other plugin setting. Bail out instead.
        if ($paramsJson !== '') {
            $decoded = json_decode($paramsJson, true);

            if (!\is_array($decoded)) {
                Log::add(
                    'csarticlesmodulemaxxed: refusing to write last_seen_joomla — existing params row is invalid JSON',
                    Log::WARNING,
                    'plg_system_csarticlesmodulemaxxed'
                );

                return;
            }

            $params = $decoded;
        } else {
            $params = [];
        }

        $params['last_seen_joomla'] = $version;

        $query = $db->createQuery()
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('params') . ' = :params')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('csarticlesmodulemaxxed'))
            ->bind(':params', json_encode($params));

        $db->setQuery($query)->execute();

        // Keep in-memory params in sync so other event handlers in the same
        // request see the new value.
        $this->params->set('last_seen_joomla', $version);
    }

    private function notifyJoomlaUpdate(string $fromVersion, string $toVersion): void
    {
        $superUsers = $this->getSuperUserRecipients();

        if (empty($superUsers)) {
            return;
        }

        $app           = $this->getApplication();
        $siteName      = (string) ($app?->get('sitename') ?? '');
        $siteUrl       = rtrim(Uri::root(), '/');
        $supportEmail  = trim((string) $this->params->get('support_email', 'support@cybersalt.com'));
        $supportLabel  = trim((string) $this->params->get('support_label', 'Cybersalt support')) ?: 'Cybersalt support';
        $pluginVersion = self::PLUGIN_VERSION;

        $this->loadLanguage();

        $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

        foreach ($superUsers as $user) {
            try {
                $thisMail = clone $mailer;
                $thisMail->isHtml(true);
                $thisMail->addRecipient($user->email, $user->name);
                $thisMail->setSubject(
                    Text::sprintf(
                        'PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_SUBJECT',
                        $toVersion,
                        $siteName !== '' ? $siteName : $siteUrl
                    )
                );
                $thisMail->setBody($this->buildNotificationHtml(
                    $user->name,
                    $siteName,
                    $siteUrl,
                    $fromVersion,
                    $toVersion,
                    $pluginVersion,
                    $supportEmail,
                    $supportLabel
                ));
                $thisMail->Send();
            } catch (\Throwable $e) {
                Log::add(
                    'csarticlesmodulemaxxed: failed to email Super User ' . $user->email . ': ' . $e->getMessage(),
                    Log::WARNING,
                    'plg_system_csarticlesmodulemaxxed'
                );
            }
        }
    }

    /**
     * Returns active Super Users (block=0, sendEmail=1) by resolving which
     * groups have core.admin on the root asset, then loading those groups'
     * users. This avoids hard-coding "group id 8" or the literal "Super Users"
     * group title (which can be renamed).
     *
     * @return array<int, object{id:int, name:string, email:string}>
     */
    private function getSuperUserRecipients(): array
    {
        $db = $this->getDatabase();

        $query = $db->createQuery()
            ->select($db->quoteName('rules'))
            ->from($db->quoteName('#__assets'))
            ->where($db->quoteName('id') . ' = 1');

        $rulesJson = (string) $db->setQuery($query)->loadResult();
        $rules     = json_decode($rulesJson, true);

        if (!\is_array($rules) || empty($rules['core.admin']) || !\is_array($rules['core.admin'])) {
            return [];
        }

        $superGroupIds = [];

        foreach ($rules['core.admin'] as $groupId => $allowed) {
            if ((int) $allowed === 1) {
                $superGroupIds[] = (int) $groupId;
            }
        }

        if (empty($superGroupIds)) {
            return [];
        }

        $query = $db->createQuery()
            ->select(['DISTINCT u.id', 'u.name', 'u.email'])
            ->from($db->quoteName('#__users', 'u'))
            ->innerJoin($db->quoteName('#__user_usergroup_map', 'm') . ' ON m.user_id = u.id')
            ->where($db->quoteName('u.block') . ' = 0')
            ->where($db->quoteName('u.sendEmail') . ' = 1')
            ->whereIn($db->quoteName('m.group_id'), $superGroupIds);

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    private function buildNotificationHtml(
        string $userName,
        string $siteName,
        string $siteUrl,
        string $fromVersion,
        string $toVersion,
        string $pluginVersion,
        string $supportEmail,
        string $supportLabel
    ): string {
        $e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $greeting    = Text::sprintf('PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_GREETING', $userName);
        $intro       = Text::sprintf('PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_INTRO', $siteName !== '' ? $siteName : $siteUrl, $siteUrl, $fromVersion, $toVersion);
        $pluginNote  = Text::sprintf('PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_PLUGIN_NOTE', $pluginVersion);
        $checkH      = Text::_('PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_CHECK_HEADING');
        $check1      = Text::_('PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_CHECK_1');
        $check2      = Text::_('PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_CHECK_2');
        $check3      = Text::_('PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_CHECK_3');
        $brokenH     = Text::_('PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_BROKEN_HEADING');
        $broken1     = Text::_('PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_BROKEN_1');
        $broken2     = Text::_('PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_BROKEN_2');
        $broken3     = Text::sprintf('PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_BROKEN_3', $supportLabel, $supportEmail);
        $footer      = Text::_('PLG_SYSTEM_CSARTICLESMODULEMAXXED_NOTIFY_EMAIL_FOOTER');

        $releasesUrl = 'https://github.com/cybersalt/cs-articles-module-maxxed/releases';
        $issuesUrl   = 'https://github.com/cybersalt/cs-articles-module-maxxed/issues';
        $repoUrl     = 'https://github.com/cybersalt/cs-articles-module-maxxed';

        return <<<HTML
<!DOCTYPE html>
<html><body style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;color:#222;max-width:640px;margin:0 auto;padding:24px;">
<p>{$e($greeting)}</p>
<p>{$e($intro)}</p>
<p>{$e($pluginNote)}</p>
<h3 style="margin-top:24px;">{$e($checkH)}</h3>
<ol>
    <li>{$e($check1)}</li>
    <li>{$e($check2)}</li>
    <li>{$e($check3)}</li>
</ol>
<h3 style="margin-top:24px;">{$e($brokenH)}</h3>
<ul>
    <li>{$e($broken1)} &mdash; <a href="{$releasesUrl}">{$releasesUrl}</a></li>
    <li>{$e($broken2)} &mdash; <a href="{$issuesUrl}">{$issuesUrl}</a></li>
    <li>{$e($broken3)}</li>
</ul>
<p style="margin-top:24px;color:#666;font-size:13px;">{$e($footer)}</p>
<hr style="margin-top:24px;border:none;border-top:1px solid #ddd;">
<p style="color:#999;font-size:12px;">&mdash; Cybersalt Articles Module Maxxed v{$e($pluginVersion)}<br>
<a href="{$repoUrl}">{$repoUrl}</a></p>
</body></html>
HTML;
    }
}
