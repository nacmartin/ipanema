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

        $dm = $this->get('doctrine_phpcr.odm.document_manager');
        $page = new Page();
        $path = $path.'/newpage';
        $page->setId($path);
        $page->setName('Name');
        $page->setLabel('Label');
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

        $dm = $this->get('doctrine_phpcr.odm.document_manager');
        $page = new MultilangPage(true);
        $parent = $dm->find(null, $path);
        $page->setParent($parent);
        $page->setName('Name');
        $dm->persist($page);
        $page->setLabel('Label-en');
        $page->setTitle('Title-en');
        $page->setBody('Body-en');
        $dm->bindTranslation($page, 'en');
        $page->setLabel('Label-de');
        $page->setTitle('Title-de');
        $page->setBody('Body-de');
        $page->setLocale('de');
        $dm->bindTranslation($page, 'de');
        $dm->persist($page);
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
}
