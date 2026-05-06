<?php
/**
 * @package     Cybersalt.Plugin
 * @subpackage  System.csarticlesmodulemaxxed
 *
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Cybersalt\Plugin\System\Csarticlesmodulemaxxed\Extension\Csarticlesmodulemaxxed;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new Csarticlesmodulemaxxed(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'csarticlesmodulemaxxed')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
