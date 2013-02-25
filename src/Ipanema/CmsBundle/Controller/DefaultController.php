<?php

namespace Ipanema\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ipanema\CmsBundle\Document\Page;
use Ipanema\CmsBundle\Document\MultilangPage;

class DefaultController extends Controller
{

    /**
     * Creates an empty page so we can edit it later
     */
    public function addPageAction($path)
    {
        $name = $this->getRequest()->get('name');

        $dm = $this->get('doctrine_phpcr.odm.document_manager');
        $page = new Page();
        $path = $path.'/'.$name;
        $page->setId($path);
        $page->setName($name);
        $page->setLabel($name);
        $page->setTitle('Title');
        $page->setBody('Body');
        $dm->persist($page);
        $dm->flush();
        return $this->redirect($this->generateUrl($page));
    }

    /**
     * Creates a multilingual page so we can edit it later
     */
    public function addMultilingualPageAction($path)
    {
        $name = $this->getRequest()->get('name');

        $dm = $this->get('doctrine_phpcr.odm.document_manager');
        $page = new MultilangPage(true);
        $parent = $dm->find(null, $path);
        $page->setParent($parent);
        $page->setName($name);
        $dm->persist($page);
        foreach (array('en', 'es', 'ca') as $locale) {
            $page->setLabel("$name-$locale");
            $page->setTitle("Title-$locale");
            $page->setBody("Body-$locale");
            $dm->bindTranslation($page, $locale);
        }
        $dm->flush();
        return $this->redirect($this->generateUrl($page));
    }

    /**
     * Removes a page
     */
    public function removePageAction($path)
    {

        $dm = $this->get('doctrine_phpcr.odm.document_manager');

        $page = $dm->find(null, $path);
        $parent = $page->getParent();
        $dm->remove($page);
        $dm->flush();
        return $this->redirect($this->generateUrl($parent));
    }
    public function dynamicAction()
    {
        return $this->render('IpanemaCmsBundle:Default:dynamic.html.twig');
    }
    public function staticAction()
    {
        return $this->render('IpanemaCmsBundle:Default:static.html.twig');
    }

    public function blockAction($block)
    {
        return $this->render('IpanemaCmsBundle:Block:action_block.html.twig', array(
            'block' => $block
        ));
    }
}
