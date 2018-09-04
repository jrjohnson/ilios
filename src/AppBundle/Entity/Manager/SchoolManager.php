<?php

namespace AppBundle\Entity\Manager;

use AppBundle\Classes\CalendarEvent;
use AppBundle\Classes\SchoolEvent;
use AppBundle\Entity\Repository\SchoolRepository;
use AppBundle\Service\UserMaterialFactory;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class SchoolManager
 */
class SchoolManager extends BaseManager
{
    /**
     * @var UserMaterialFactory
     */
    protected $factory;

    /**
     * @param RegistryInterface $registry
     * @param string $class
     * @param UserMaterialFactory $factory
     */
    public function __construct(RegistryInterface $registry, $class, UserMaterialFactory $factory)
    {
        parent::__construct($registry, $class);
        $this->factory = $factory;
    }

    /**
     * @param int $schoolId
     * @param \DateTime $from
     * @param \DateTime $to
     * @return SchoolEvent[]
     * @throws \Exception
     */
    public function findEventsForSchool($schoolId, \DateTime $from, \DateTime $to)
    {
        /** @var SchoolRepository $repository */
        $repository = $this->getRepository();
        return $repository->findEventsForSchool($schoolId, $from, $to);
    }

    /**
     * Finds and adds instructors to a given list of calendar events.
     *
     * @param CalendarEvent[] $events
     * @return CalendarEvent[]
     * @throws \Exception
     */
    public function addInstructorsToEvents(array $events)
    {
        /** @var SchoolRepository $repository */
        $repository = $this->getRepository();
        return $repository->addInstructorsToEvents($events);
    }

    /**
     * Finds and adds learning materials to a given list of user events.
     *
     * @param CalendarEvent[] $events
     * @return CalendarEvent[]
     * @throws \Exception
     */
    public function addMaterialsToEvents(array $events)
    {
        /** @var SchoolRepository $repository */
        $repository = $this->getRepository();
        return $repository->addMaterialsToEvents($events, $this->factory);
    }

    /**
     * Finds and adds course- and session-objectives and their competencies to a given list of calendar events.
     *
     * @param CalendarEvent[] $events
     * @return CalendarEvent[]
     * @throws \Exception
     */
    public function addObjectivesAndCompetenciesToEvents(array $events)
    {
        /** @var SchoolRepository $repository */
        $repository = $this->getRepository();
        return $repository->addObjectivesAndCompetenciesToEvents($events);
    }
}