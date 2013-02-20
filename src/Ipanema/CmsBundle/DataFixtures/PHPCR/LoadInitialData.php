<?php

namespace Symfony\Cmf\Bundle\SimpleCmsBundle\DataFixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Yaml\Parser;

use Symfony\Cmf\Bundle\SimpleCmsBundle\Document\MultilangRedirectRoute;
use Symfony\Cmf\Bundle\SimpleCmsBundle\Document\MultilangRoute;

use Symfony\Cmf\Bundle\MenuBundle\Document\MultilangMenuNode;

use Symfony\Component\DependencyInjection\ContainerAware;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;

use Symfony\Cmf\Bundle\SimpleCmsBundle\DataFixtures\LoadCmsData;

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

    public function load(ObjectManager $dm)
    {
        $this->yaml = new Parser();

        parent::load($dm);

        #$data = $this->yaml->parse(file_get_contents(__DIR__ . '/../../Resources/data/external.yml'));

        #$basepath = $this->container->getParameter('ipanema_cms.basepath');
        #$home = $dm->find(null, $basepath);

        #$route = new MultilangRoute();
        #$route->setPosition($home, 'dynamic');
        #$route->setDefault('_controller', 'AcmeMainBundle:Demo:dynamic');

        #$dm->persist($route);

        #foreach ($data['static'] as $name => $overview) {
        #    $menuItem = new MultilangMenuNode();
        #    $menuItem->setName($name);
        #    $menuItem->setParent($home);
        #    if (!empty($overview['route'])) {
        #        if (!empty($overview['uri'])) {
        #            $route = new MultilangRedirectRoute();
        #            $route->setPosition($home, $overview['route']);
        #            $route->setUri($overview['uri']);
        #            $dm->persist($route);
        #        } else {
        #            $route = $dm->find(null, $basepath.'/'.$overview['route']);
        #        }
        #        $menuItem->setRoute($route->getId());
        #    } else {
        #        $menuItem->setUri($overview['uri']);
        #    }
        #    $dm->persist($menuItem);
        #    foreach ($overview['label'] as $locale => $label) {
        #        $menuItem->setLabel($label);
        #        if ($locale) {
        #            $dm->bindTranslation($menuItem, $locale);
        #        }
        #    }
        #}
        #$dm->flush();
    }

}

