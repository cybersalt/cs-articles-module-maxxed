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

use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

final class Csarticlesmodulemaxxed extends CMSPlugin implements SubscriberInterface
{
    private const PARAM_KEY = 'cs_skip_articles';

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
        ];
    }

    public function onContentPrepareForm(Event $event): void
    {
        $form = $this->resolveForm($event);

        if (!$form instanceof Form || $form->getName() !== 'com_modules.module') {
            return;
        }

        $data    = $this->resolveArg($event, 'data');
        $element = $this->extractModuleElement($data);

        if ($element === '' || !$this->isTargetEnabled($element)) {
            return;
        }

        // The plugin's frontend .ini doesn't auto-load on the module edit form
        // (Joomla only auto-loads .sys.ini globally), so the field's label and
        // description would render as raw language constants without this.
        $this->loadLanguage();

        Form::addFormPath(\dirname(__DIR__, 2) . '/forms');
        $form->loadFile('skip-articles-field', false);
    }

    public function onAfterModuleList(Event $event): void
    {
        $modules = $this->resolveArg($event, 'modules');

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
    }

    public function onAfterRenderModule(Event $event): void
    {
        $module = $this->resolveArg($event, 'subject');

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

    private function resolveForm(Event $event): ?Form
    {
        // Concrete PrepareFormEvent classes carry the form as 'subject'; legacy
        // events sometimes name it 'form'. Try both.
        $form = $this->resolveArg($event, 'subject') ?? $this->resolveArg($event, 'form');

        return $form instanceof Form ? $form : null;
    }

    private function resolveArg(Event $event, string $name): mixed
    {
        // Joomla 5/6 concrete events expose getX() helpers; the underlying
        // arguments array still works for everything.
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
}
