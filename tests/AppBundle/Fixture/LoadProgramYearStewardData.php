<?php

namespace Tests\AppBundle\Fixture;

use AppBundle\Entity\ProgramYearSteward;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadProgramYearStewardData extends AbstractFixture implements
    FixtureInterface,
    DependentFixtureInterface,
    ContainerAwareInterface
{

    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $data = $this->container
            ->get('Tests\AppBundle\DataLoader\ProgramYearStewardData')
            ->getAll();
        foreach ($data as $arr) {
            $entity = new ProgramYearSteward();
            $entity->setId($arr['id']);
            $entity->setSchool($this->getReference('schools' . $arr['school']));
            $entity->setDepartment($this->getReference('departments' . $arr['department']));
            $entity->setProgramYear($this->getReference('programYears' . $arr['programYear']));
            
            $manager->persist($entity);
            $this->addReference('programYearStewards' . $arr['id'], $entity);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(
            'Tests\AppBundle\Fixture\LoadSchoolData',
            'Tests\AppBundle\Fixture\LoadProgramYearData',
            'Tests\AppBundle\Fixture\LoadDepartmentData',
        );
    }
}