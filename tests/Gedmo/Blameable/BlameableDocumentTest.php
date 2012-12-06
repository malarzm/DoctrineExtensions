<?php

namespace Gedmo\Blameable;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Blameable\Fixture\Document\Article,
    Blameable\Fixture\Document\Type;

/**
 * These are tests for Blameable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Blameable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BlameableDocumentTest extends BaseTestCaseMongoODM
{
    const ARTICLE = 'Blameable\Fixture\Document\Article';
    const TYPE = 'Blameable\Fixture\Document\Type';

    protected function setUp()
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new BlameableListener);

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    public function testBlameable()
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneByTitle('Blameable Article');

        $date = new \DateTime();
        $this->assertEquals(time(), (string)$article->getCreated());
        $this->assertEquals(
            $date->format('Y-m-d H:i'),
            $article->getUpdated()->format('Y-m-d H:i')
        );

        $published = new Type;
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $article->setType($published);
        $this->dm->persist($article);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->findOneByTitle('Blameable Article');
        $date = new \DateTime();
        $this->assertEquals(
            $date->format('Y-m-d H:i'),
            $article->getPublished()->format('Y-m-d H:i')
        );
    }

    public function testForcedValues()
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $created = strtotime('2000-01-01 12:00:00');
        $sport->setCreated($created);
        $sport->setUpdated(new \DateTime('2000-01-01 12:00:00'));

        $this->dm->persist($sport);
        $this->dm->flush();
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::ARTICLE);
        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals(
            $created,
            (string)$sport->getCreated()
        );
        $this->assertEquals(
            '2000-01-01 12:00:00',
            $sport->getUpdated()->format('Y-m-d H:i:s')
        );

        $published = new Type;
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(new \DateTime('2000-01-01 12:00:00'));
        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals(
            '2000-01-01 12:00:00',
            $sport->getPublished()->format('Y-m-d H:i:s')
        );
    }

    private function populate()
    {
        $art0 = new Article();
        $art0->setTitle('Blameable Article');

        $this->dm->persist($art0);
        $this->dm->flush();
        $this->dm->clear();
    }
}