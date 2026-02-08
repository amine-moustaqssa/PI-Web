<?php

namespace App\Tests\Controller;

use App\Entity\RapportMedical;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RapportMedicalControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $rapportMedicalRepository;
    private string $path = '/rapport/medical/controller/php/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->rapportMedicalRepository = $this->manager->getRepository(RapportMedical::class);

        foreach ($this->rapportMedicalRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('RapportMedical index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'rapport_medical[contenu]' => 'Testing',
            'rapport_medical[conclusion]' => 'Testing',
            'rapport_medical[url_pdf]' => 'Testing',
            'rapport_medical[date_creation]' => 'Testing',
            'rapport_medical[dossierClinique]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->rapportMedicalRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new RapportMedical();
        $fixture->setContenu('My Title');
        $fixture->setConclusion('My Title');
        $fixture->setUrl_pdf('My Title');
        $fixture->setDate_creation('My Title');
        $fixture->setDossierClinique('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('RapportMedical');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new RapportMedical();
        $fixture->setContenu('Value');
        $fixture->setConclusion('Value');
        $fixture->setUrl_pdf('Value');
        $fixture->setDate_creation('Value');
        $fixture->setDossierClinique('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'rapport_medical[contenu]' => 'Something New',
            'rapport_medical[conclusion]' => 'Something New',
            'rapport_medical[url_pdf]' => 'Something New',
            'rapport_medical[date_creation]' => 'Something New',
            'rapport_medical[dossierClinique]' => 'Something New',
        ]);

        self::assertResponseRedirects('/rapport/medical/controller/php/');

        $fixture = $this->rapportMedicalRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getContenu());
        self::assertSame('Something New', $fixture[0]->getConclusion());
        self::assertSame('Something New', $fixture[0]->getUrl_pdf());
        self::assertSame('Something New', $fixture[0]->getDate_creation());
        self::assertSame('Something New', $fixture[0]->getDossierClinique());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new RapportMedical();
        $fixture->setContenu('Value');
        $fixture->setConclusion('Value');
        $fixture->setUrl_pdf('Value');
        $fixture->setDate_creation('Value');
        $fixture->setDossierClinique('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/rapport/medical/controller/php/');
        self::assertSame(0, $this->rapportMedicalRepository->count([]));
    }
}
