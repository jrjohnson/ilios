<?php

namespace Tests\AppBundle\DataFixtures\ORM;

use AppBundle\Entity\SessionTypeInterface;

/**
 * Class LoadSessionTypeDataTest
 */
class LoadSessionTypeDataTest extends AbstractDataFixtureTest
{
    /**
     * {@inheritdoc}
     */
    public function getEntityManagerServiceKey()
    {
        return 'AppBundle\Entity\Manager\SessionTypeManager';
    }

    /**
     * {@inheritdoc}
     */
    public function getFixtures()
    {
        return [
            'AppBundle\DataFixtures\ORM\LoadSessionTypeData',
        ];
    }

    /**
     * @covers \AppBundle\DataFixtures\ORM\LoadSessionTypeData::load
     */
    public function testLoad()
    {
        $this->runTestLoad('session_type.csv');
    }

    /**
     * @param array $data
     * @param SessionTypeInterface $entity
     */
    protected function assertDataEquals(array $data, $entity)
    {
        // `session_type_id`,`title`,`school_id`,`calendar_color`,`assessment`,`assessment_option_id`
        $this->assertEquals($data[0], $entity->getId());
        $this->assertEquals($data[1], $entity->getTitle());
        $this->assertEquals($data[2], $entity->getSchool()->getId());
        $this->assertEquals($data[3], $entity->getCalendarColor());
        $this->assertEquals((boolean) $data[4], $entity->isAssessment());
        if (empty($data[5])) {
            $this->assertNull($entity->getAssessmentOption());
        } else {
            $this->assertEquals($data[5], $entity->getAssessmentOption());
        }
    }
}