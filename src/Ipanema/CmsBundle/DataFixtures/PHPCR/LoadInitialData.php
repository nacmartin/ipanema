<?php

namespace Symfony\Cmf\Bundle\SimpleCmsBundle\DataFixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Yaml\Parser;

use Ipanema\CmsBundle\Document\MultilangRedirectRoute;
use Ipanema\CmsBundle\Document\MultilangRoute;

use Symfony\Cmf\Bundle\MenuBundle\Document\MultilangMenuNode;

use Symfony\Component\DependencyInjection\ContainerAware;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;

use Symfony\Cmf\Bundle\SimpleCmsBundle\DataFixtures\LoadCmsData;

use PHPCR\Util\NodeHelper;

class LoadInitialData extends LoadCmsData
{
    private $yaml;

    protected $defaultClass = array(
        'multilangpage' => 'Ipanema\CmsBundle\Document\MultilangPage',
        'page' => 'Ipanema\CmsBundle\Document\Page'
    );

    public function getOrder()
    {
        return 1;
    }


    protected function getData()
    {
        return $this->yaml->parse(file_get_contents(__DIR__.'/../../Resources/data/page.yml'));
    }

    protected function getBasePath()
    {
        return $this->container->getParameter('ipanema_cms.basepath');
    }

    protected function getDefaultClass()
    {
        return $this->container->getParameter('ipanema_cms.locales')
            ? $this->defaultClass['multilangpage'] : $this->defaultClass['page'];
    }

    protected function loadPages(ObjectManager $dm)
    {
        $session = $dm->getPhpcrSession();

        $basepath = $this->getBasePath();
        NodeHelper::createPath($session, preg_replace('#/[^/]*$#', '', $basepath));

        $data = $this->getData();

        $defaultClass = $this->getDefaultClass();

        foreach ($data['static'] as $overview) {
            $class = isset($overview['class']) ? $overview['class'] : $defaultClass;

            $parent = (isset($overview['parent']) ? trim($overview['parent'], '/') : '');
            $name = (isset($overview['name']) ? trim($overview['name'], '/') : '');

            $path = $basepath
                .(empty($parent) ? '' : '/' . $parent)
                .(empty($name) ? '' : '/' . $name);

            $page = $dm->find($class, $path);
            if (!$page) {
                $page = $this->createPageInstance($class);
                $page->setId($path);
            }

            if (isset($overview['formats'])) {
                $page->setDefault('_format', reset($overview['formats']));
                $page->setRequirement('_format', implode('|', $overview['formats']));
            }

            if (!empty($overview['template'])) {
                $page->setDefault(RouteObjectInterface::TEMPLATE_NAME, $overview['template']);
            }

            if (!empty($overview['controller'])) {
                $page->setDefault(RouteObjectInterface::CONTROLLER_NAME, $overview['controller']);
            }

            if (!empty($overview['options'])) {
                $page->setOptions($overview['options']);
            }

            $dm->persist($page);

            if (is_array($overview['title'])) {
                foreach ($overview['title'] as $locale => $title) {
                    $page->setTitle($title);
                    if (isset($overview['label'][$locale]) && $overview['label'][$locale]) {
                        $page->setLabel($overview['label'][$locale]);
                    } elseif (!isset($overview['label'][$locale])) {
                        $page->setLabel($title);
                    }
                    $page->setBody($overview['body'][$locale]);
                    $dm->bindTranslation($page, $locale);
                }
            } else {
                $page->setTitle($overview['title']);
                if (isset($overview['label'])) {
                    if ($overview['label']) {
                        $page->setLabel($overview['label']);
                    }
                } elseif (!isset($overview['label'])) {
                    $page->setLabel($overview['title']);
                }
                $page->setBody($overview['body']);
            }

            if (isset($overview['create_date'])) {
                $page->setCreateDate(date_create_from_format('U', strtotime($overview['create_date'])));
            }

            if (isset($overview['publish_start_date'])) {
                $page->setPublishStartDate(date_create_from_format('U', strtotime($overview['publish_start_date'])));
            }

            if (isset($overview['publish_end_date'])) {
                $page->setPublishEndDate(date_create_from_format('U', strtotime($overview['publish_end_date'])));
            }

            if (isset($overview['blocks'])) {
                foreach ($overview['blocks'] as $name => $block) {
                    $this->loadBlock($dm, $page, $name, $block);
                }
            }
        }

        $dm->flush();
    }
    public function load(ObjectManager $dm)
    {
        $this->yaml = new Parser();

        $this->loadPages($dm);

        $data = $this->yaml->parse(file_get_contents(__DIR__ . '/../../Resources/data/external.yml'));

        $basepath = $this->container->getParameter('ipanema_cms.basepath');
        $home = $dm->find(null, $basepath);

        $route = new MultilangRoute();
        $route->setPosition($home, 'dynamic');
        $route->setDefault('_controller', 'IpanemaCmsBundle:Default:dynamic');

        $dm->persist($route);

        foreach ($data['static'] as $name => $overview) {
            $menuItem = new MultilangMenuNode();
            $menuItem->setName($name);
            $menuItem->setParent($home);
            if (!empty($overview['route'])) {
                if (!empty($overview['uri'])) {
                    $route = new MultilangRedirectRoute();
                    $route->setPosition($home, $overview['route']);
                    $route->setUri($overview['uri']);
                    $dm->persist($route);
                } else {
                    $route = $dm->find(null, $basepath.'/'.$overview['route']);
                }
                $menuItem->setRoute($route->getId());
            } elseif (!empty($overview['symfony_route'])) {
                $menuItem->setRoute($overview['symfony_route']);
            } else {
                $menuItem->setUri($overview['uri']);
            }
            $dm->persist($menuItem);
            foreach ($overview['label'] as $locale => $label) {
                $menuItem->setLabel($label);
                if ($locale) {
                    $dm->bindTranslation($menuItem, $locale);
                }
            }
        }

        $dm->flush();
    }

    /**
     * Load a block from the fixtures and create / update the node. Recurse if there are children.
     *
     * @param ObjectManager $manager the document manager
     * @param string $parentPath the parent of the block
     * @param string $name the name of the block
     * @param array $block the block definition
     */
    private function loadBlock(ObjectManager $manager, $parent, $name, $block)
    {
        $className = $block['class'];
        $document = $manager->find(null, $this->getIdentifier($manager, $parent) . '/' . $name);
        $class = $manager->getClassMetadata($className);
        if ($document && get_class($document) != $className) {
            $manager->remove($document);
            $document = null;
        }
        if (!$document) {
            $document = $class->newInstance();

            // $document needs to be an instance of BaseBlock ...
            $document->setParentDocument($parent);
            $document->setName($name);
            $manager->persist($document);
        }

        if ($className == 'Symfony\Cmf\Bundle\BlockBundle\Document\ReferenceBlock') {
            $referencedBlock = $manager->find(null, $block['referencedBlock']);
            if (null == $referencedBlock) {
                throw new \Exception('did not find '.$block['referencedBlock']);
            }
            $document->setReferencedBlock($referencedBlock);
        } elseif ($className == 'Symfony\Cmf\Bundle\BlockBundle\Document\ActionBlock') {
            $document->setActionName($block['actionName']);
        } elseif ($className == 'Symfony\Cmf\Bundle\BlockBundle\Document\RssBlock') {
            $document->setSetting('url', $block['url']);
        }

        // set properties
        if (isset($block['properties'])) {
            foreach ($block['properties'] as $propName => $prop) {
                $class->reflFields[$propName]->setValue($document, $prop);
            }
        }
        // create children
        if (isset($block['children'])) {
            foreach ($block['children'] as $childName => $child) {
                $this->loadBlock($manager, $document, $childName, $child);
            }
        }
    }

    private function getIdentifier($manager, $document)
    {
        $class = $manager->getClassMetadata(get_class($document));
        return $class->getIdentifierValue($document);
    }
}
