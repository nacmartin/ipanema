<?php

namespace Ipanema\CmsBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class IpanemaCmsExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('routing.xml');

        $container->setParameter($this->getAlias() . '.basepath', $config['basepath']);
        $container->setParameter($this->getAlias() . '.uri_filter_regexp', $config['routing']['uri_filter_regexp']);

        $dynamic = $container->getDefinition($this->getAlias().'.dynamic_router');
        $dynamic = $container->getDefinition($this->getAlias().'.dynamic_router');

        if (!empty($config['generic_controller'])) {
            $definition = new DefinitionDecorator('symfony_cmf_routing_extra.enhancer_explicit_template');
            $definition->replaceArgument(2, $config['generic_controller']);
            $container->setDefinition($this->getAlias() . '.enhancer_explicit_template', $definition);
            $dynamic->addMethodCall('addRouteEnhancer', array(new Reference($this->getAlias() . '.enhancer_explicit_template')));
        }
        if (!empty($config['routing']['controllers_by_alias'])) {
            $definition = new DefinitionDecorator('symfony_cmf_routing_extra.enhancer_controllers_by_class');
            $definition->replaceArgument(2, $config['routing']['controllers_by_alias']);
            $container->setDefinition($this->getAlias() . '.enhancer_controllers_by_class', $definition);
            $dynamic->addMethodCall('addRouteEnhancer', array(new Reference($this->getAlias() . '.enhancer_controllers_by_alias')));
        }
        if (!empty($config['routing']['controllers_by_class'])) {
            $definition = new DefinitionDecorator('symfony_cmf_routing_extra.enhancer_controllers_by_class');
            $definition->replaceArgument(2, $config['routing']['controllers_by_class']);
            $container->setDefinition($this->getAlias() . '.enhancer_controllers_by_class', $definition);
            $dynamic->addMethodCall('addRouteEnhancer', array(new Reference($this->getAlias() . '.enhancer_controllers_by_class')));
        }
        if (!empty($config['generic_controller']) && !empty($config['routing']['templates_by_class'])) {
            $controllerForTemplates = array();
            foreach ($config['routing']['templates_by_class'] as $key => $value) {
                $controllerForTemplates[$key] = $config['generic_controller'];
            }

            $definition = new DefinitionDecorator('symfony_cmf_routing_extra.enhancer_controller_for_templates_by_class');
            $definition->replaceArgument(2, $controllerForTemplates);
            $container->setDefinition($this->getAlias() . '.enhancer_controller_for_templates_by_class', $definition);
            $definition = new DefinitionDecorator('symfony_cmf_routing_extra.enhancer_templates_by_class');
            $definition->replaceArgument(2, $config['routing']['templates_by_class']);
            $container->setDefinition($this->getAlias() . '.enhancer_templates_by_class', $definition);
            $dynamic->addMethodCall('addRouteEnhancer', array(new Reference($this->getAlias() . '.enhancer_controller_for_templates_by_class')));
            $dynamic->addMethodCall('addRouteEnhancer', array(new Reference($this->getAlias() . '.enhancer_templates_by_class')));
        }

        $generator = $container->getDefinition($this->getAlias().'.generator');
        $generator->addMethodCall('setContentRepository', array(new Reference($config['routing']['content_repository_id'])));

        if (!empty($config['multilang'])) {
            $container->setParameter($this->getAlias() . '.locales', $config['multilang']['locales']);
            $container->setAlias('ipanema_cms.route_provider', 'ipanema_cms.multilang_route_provider');
            if ('Ipanema\CmsBundle\Document\Page' === $config['document_class']) {
                $config['document_class'] = 'Ipanema\CmsBundle\Document\MultilangPage';
            }
        }

        $container->setParameter($this->getAlias() . '.document_class', $config['document_class']);

        if ($config['use_menu']) {
            $this->loadMenu($config, $loader, $container);
        }

        if ($config['use_sonata_admin']) {
            $this->loadSonataAdmin($config, $loader, $container);
        }

    }
    protected function loadMenu($config, XmlFileLoader $loader, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        if ('auto' === $config['use_menu'] && !isset($bundles['SymfonyCmfMenuBundle'])) {
            return;
        }

        $loader->load('menu.xml');
    }
    protected function loadSonataAdmin($config, XmlFileLoader $loader, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        if ('auto' === $config['use_sonata_admin'] && !isset($bundles['SonataDoctrinePHPCRAdminBundle'])) {
            return;
        }

        $loader->load('admin.xml');
    }

}
