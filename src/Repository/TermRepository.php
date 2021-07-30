<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Term;
use App\Traits\ManagerRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use App\Entity\DTO\TermDTO;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\TermInterface;

class TermRepository extends ServiceEntityRepository implements
    DTORepositoryInterface,
    RepositoryInterface,
    DataImportRepositoryInterface
{
    use ManagerRepository;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Term::class);
    }

    /**
     * Custom findBy so we can filter by related entities
     *
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     * @return TermInterface[]
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select('DISTINCT t')->from(Term::class, 't');

        $this->attachCriteriaToQueryBuilder($qb, $criteria, $orderBy, $limit, $offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find and hydrate as DTOs
     *
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     * @return TermDTO[]
     */
    public function findDTOsBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
    {
        $qb = $this->_em->createQueryBuilder()->select('t')->distinct()->from(Term::class, 't');
        $this->attachCriteriaToQueryBuilder($qb, $criteria, $orderBy, $limit, $offset);

        $termDTOs = [];
        foreach ($qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY) as $arr) {
            $termDTOs[$arr['id']] = new TermDTO(
                $arr['id'],
                $arr['title'],
                $arr['description'],
                $arr['active']
            );
        }

        return $this->attachAssociationsToDTOs($termDTOs);
    }

    protected function attachAssociationsToDTOs(array $termDTOs): array
    {
        $termIds = array_keys($termDTOs);

        $qb = $this->_em->createQueryBuilder();
        $qb->select('t.id AS termId, v.id AS vocabularyId, p.id AS parentId, s.id AS schoolId')
            ->from(Term::class, 't')
            ->join('t.vocabulary', 'v')
            ->join('v.school', 's')
            ->leftJoin('t.parent', 'p')
            ->where($qb->expr()->in('t.id', ':termIds'))
            ->setParameter('termIds', $termIds);

        foreach ($qb->getQuery()->getResult() as $arr) {
            $termDTOs[$arr['termId']]->vocabulary = $arr['vocabularyId'];
            $termDTOs[$arr['termId']]->parent = $arr['parentId'] ? $arr['parentId'] : null;
            $termDTOs[$arr['termId']]->school = $arr['schoolId'];
        }

        $related = [
            'children',
            'courses',
            'programYears',
            'sessions',
            'aamcResourceTypes',
            'programYearObjectives',
            'courseObjectives',
            'sessionObjectives'
        ];

        foreach ($related as $rel) {
            $qb = $this->_em->createQueryBuilder()
                ->select('r.id as relId, t.id AS termId')->from(Term::class, 't')
                ->join("t.{$rel}", 'r')
                ->where($qb->expr()->in('t.id', ':termIds'))
                ->orderBy('relId')
                ->setParameter('termIds', $termIds);

            foreach ($qb->getQuery()->getResult() as $arr) {
                $termDTOs[$arr['termId']]->{$rel}[] = $arr['relId'];
            }
        }

        return array_values($termDTOs);
    }

    /**
     * Custom findBy so we can filter by related entities
     *
     * @param array $criteria
     * @param array $orderBy
     * @param int $limit
     * @param int $offset
     * @return QueryBuilder
     */
    protected function attachCriteriaToQueryBuilder(QueryBuilder $qb, $criteria, $orderBy, $limit, $offset)
    {
        if (array_key_exists('courses', $criteria)) {
            $ids = is_array($criteria['courses']) ? $criteria['courses'] : [$criteria['courses']];
            $qb->leftJoin('t.courses', 'cr_course');
            $qb->leftJoin('t.sessions', 'cr_session');
            $qb->leftJoin('cr_session.course', 'cr_course2');
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('cr_course.id', ':courses'),
                    $qb->expr()->in('cr_course2.id', ':courses')
                )
            );
            $qb->setParameter(':courses', $ids);
        }

        if (array_key_exists('sessions', $criteria)) {
            $ids = is_array($criteria['sessions']) ? $criteria['sessions'] : [$criteria['sessions']];
            $qb->join('t.sessions', 'se_session');
            $qb->andWhere($qb->expr()->in('se_session.id', ':sessions'));
            $qb->setParameter(':sessions', $ids);
        }

        if (array_key_exists('programYears', $criteria)) {
            $ids = is_array($criteria['programYears']) ? $criteria['programYears'] : [$criteria['programYears']];
            $qb->join('t.programYears', 'py_programyear');
            $qb->andWhere($qb->expr()->in('py_programyear.id', ':programYears'));
            $qb->setParameter(':programYears', $ids);
        }

        if (array_key_exists('sessionTypes', $criteria)) {
            $ids = is_array($criteria['sessionTypes']) ? $criteria['sessionTypes'] : [$criteria['sessionTypes']];
            $qb->join('t.sessions', 'st_session');
            $qb->join('st_session.sessionType', 'st_sessionType');
            $qb->andWhere($qb->expr()->in('st_sessionType.id', ':sessionTypes'));
            $qb->setParameter(':sessionTypes', $ids);
        }

        if (array_key_exists('programs', $criteria)) {
            $ids = is_array($criteria['programs']) ? $criteria['programs'] : [$criteria['programs']];
            $qb->join('t.programYears', 'p_programYear');
            $qb->join('p_programYear.program', 'p_program');
            $qb->andWhere($qb->expr()->in('p_program.id', ':programs'));
            $qb->setParameter(':programs', $ids);
        }

        if (array_key_exists('instructors', $criteria)) {
            $ids = is_array($criteria['instructors']) ? $criteria['instructors'] : [$criteria['instructors']];
            $qb->join('t.sessions', 'i_session');
            $qb->leftJoin('i_session.offerings', 'i_offering');
            $qb->leftJoin('i_offering.instructors', 'i_instructor');
            $qb->leftJoin('i_offering.instructorGroups', 'i_iGroup');
            $qb->leftJoin('i_iGroup.users', 'i_instructor2');
            $qb->leftJoin('i_session.ilmSession', 'i_ilm');
            $qb->leftJoin('i_ilm.instructors', 'i_instructor3');
            $qb->leftJoin('i_ilm.instructorGroups', 'i_iGroup2');
            $qb->leftJoin('i_iGroup2.users', 'i_instructor4');
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('i_instructor.id', ':instructors'),
                    $qb->expr()->in('i_instructor2.id', ':instructors'),
                    $qb->expr()->in('i_instructor3.id', ':instructors'),
                    $qb->expr()->in('i_instructor4.id', ':instructors')
                )
            );
            $qb->setParameter(':instructors', $ids);
        }

        if (array_key_exists('instructorGroups', $criteria)) {
            $ids = is_array($criteria['instructorGroups'])
                ? $criteria['instructorGroups'] : [$criteria['instructorGroups']];
            $qb->join('t.sessions', 'ig_session');
            $qb->leftJoin('ig_session.offerings', 'ig_offering');
            $qb->leftJoin('ig_offering.instructorGroups', 'ig_iGroup');
            $qb->leftJoin('ig_session.ilmSession', 'ig_ilm');
            $qb->leftJoin('ig_ilm.instructorGroups', 'ig_iGroup2');
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('ig_iGroup.id', ':iGroups'),
                    $qb->expr()->in('ig_iGroup2.id', ':iGroups')
                )
            );
            $qb->setParameter(':iGroups', $ids);
        }

        if (array_key_exists('learningMaterials', $criteria)) {
            $ids = is_array($criteria['learningMaterials'])
                ? $criteria['learningMaterials'] : [$criteria['learningMaterials']];
            $qb->leftJoin('t.courses', 'lm_course');
            $qb->leftJoin('t.sessions', 'lm_session');
            $qb->leftJoin('lm_course.learningMaterials', 'lm_clm');
            $qb->leftJoin('lm_session.learningMaterials', 'lm_slm');
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('lm_slm.id', ':learningMaterials'),
                    $qb->expr()->in('lm_clm.id', ':learningMaterials')
                )
            );
            $qb->setParameter(':learningMaterials', $ids);
        }

        if (array_key_exists('competencies', $criteria)) {
            $ids = is_array($criteria['competencies']) ? $criteria['competencies'] : [$criteria['competencies']];
            $qb->leftJoin('t.courses', 'cm_course');
            $qb->leftJoin('t.sessions', 'cm_session');
            $qb->leftJoin('cm_course.courseObjectives', 'cm_course_x_objective');
            $qb->leftJoin('cm_course_x_objective.programYearObjectives', 'cm_program_year_objective');
            $qb->leftJoin('cm_program_year_objective.competency', 'cm_competency');
            $qb->leftJoin('cm_competency.parent', 'cm_competency2');
            $qb->leftJoin('cm_session.sessionObjectives', 'cm_session_x_objective');
            $qb->leftJoin('cm_session_x_objective.courseObjectives', 'cm_course_objective2');
            $qb->leftJoin('cm_course_objective2.programYearObjectives', 'cm_program_year_objective2');
            $qb->leftJoin('cm_program_year_objective2.competency', 'cm_competency3');
            $qb->leftJoin('cm_competency3.parent', 'cm_competency4');
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('cm_competency.id', ':competencies'),
                    $qb->expr()->in('cm_competency2.id', ':competencies'),
                    $qb->expr()->in('cm_competency3.id', ':competencies'),
                    $qb->expr()->in('cm_competency4.id', ':competencies')
                )
            );
            $qb->setParameter(':competencies', $ids);
        }

        if (array_key_exists('meshDescriptors', $criteria)) {
            $ids = is_array($criteria['meshDescriptors'])
                ? $criteria['meshDescriptors'] : [$criteria['meshDescriptors']];
            $qb->leftJoin('t.courses', 'm_course');
            $qb->leftJoin('t.sessions', 'm_session');
            $qb->leftJoin('m_course.meshDescriptors', 'm_meshDescriptor');
            $qb->leftJoin('m_session.meshDescriptors', 'm_meshDescriptor2');
            $qb->leftJoin('m_session.course', 'm_course2');
            $qb->leftJoin('m_course2.meshDescriptors', 'm_meshDescriptor3');
            $qb->leftJoin('m_course.learningMaterials', 'm_clm');
            $qb->leftJoin('m_clm.meshDescriptors', 'm_meshDescriptor4');
            $qb->leftJoin('m_session.learningMaterials', 'm_slm');
            $qb->leftJoin('m_slm.meshDescriptors', 'm_meshDescriptor5');
            $qb->leftJoin('m_course2.learningMaterials', 'm_clm2');
            $qb->leftJoin('m_clm.meshDescriptors', 'm_meshDescriptor6');
            $qb->leftJoin('m_course.courseObjectives', 'm_course_x_objective');
            $qb->leftJoin('m_course_x_objective.meshDescriptors', 'm_meshDescriptor7');
            $qb->leftJoin('m_session.sessionObjectives', 'm_session_x_objective');
            $qb->leftJoin('m_session_x_objective.meshDescriptors', 'm_meshDescriptor8');
            $qb->leftJoin('m_course2.courseObjectives', 'm_course_x_objective2');
            $qb->leftJoin('m_course_x_objective2.meshDescriptors', 'm_meshDescriptor9');
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('m_meshDescriptor.id', ':meshDescriptors'),
                    $qb->expr()->in('m_meshDescriptor2.id', ':meshDescriptors'),
                    $qb->expr()->in('m_meshDescriptor3.id', ':meshDescriptors'),
                    $qb->expr()->in('m_meshDescriptor4.id', ':meshDescriptors'),
                    $qb->expr()->in('m_meshDescriptor5.id', ':meshDescriptors'),
                    $qb->expr()->in('m_meshDescriptor6.id', ':meshDescriptors'),
                    $qb->expr()->in('m_meshDescriptor7.id', ':meshDescriptors'),
                    $qb->expr()->in('m_meshDescriptor8.id', ':meshDescriptors'),
                    $qb->expr()->in('m_meshDescriptor9.id', ':meshDescriptors')
                )
            );
            $qb->setParameter(':meshDescriptors', $ids);
        }

        if (array_key_exists('schools', $criteria)) {
            $ids = is_array($criteria['schools']) ? $criteria['schools'] : [$criteria['schools']];
            $qb->join('t.vocabulary', 'sc_vocabulary');
            $qb->join('sc_vocabulary.school', 'sc_school');
            $qb->andWhere($qb->expr()->in('sc_school.id', ':schools'));
            $qb->setParameter(':schools', $ids);
        }

        if (array_key_exists('aamcResourceTypes', $criteria)) {
            $ids = is_array(
                $criteria['aamcResourceTypes']
            ) ? $criteria['aamcResourceTypes'] : [$criteria['aamcResourceTypes']];
            $qb->join('t.aamcResourceTypes', 'art_resourceTypes');
            $qb->andWhere($qb->expr()->in('art_resourceTypes.id', ':aamcResourceTypes'));
            $qb->setParameter(':aamcResourceTypes', $ids);
        }

        if (array_key_exists('programYearObjectives', $criteria)) {
            $ids = is_array(
                $criteria['programYearObjectives']
            ) ? $criteria['programYearObjectives'] : [$criteria['programYearObjectives']];
            $qb->join('t.programYearObjectives', 'pyo_programYearObjectives');
            $qb->andWhere($qb->expr()->in('pyo_programYearObjectives.id', ':programYearObjectives'));
            $qb->setParameter(':programYearObjectives', $ids);
        }

        if (array_key_exists('sessionObjectives', $criteria)) {
            $ids = is_array(
                $criteria['sessionObjectives']
            ) ? $criteria['sessionObjectives'] : [$criteria['sessionObjectives']];
            $qb->join('t.sessionObjectives', 'so_sessionObjectives');
            $qb->andWhere($qb->expr()->in('so_sessionObjectives.id', ':sessionObjectives'));
            $qb->setParameter(':sessionObjectives', $ids);
        }

        if (array_key_exists('courseObjectives', $criteria)) {
            $ids = is_array(
                $criteria['courseObjectives']
            ) ? $criteria['courseObjectives'] : [$criteria['courseObjectives']];
            $qb->join('t.courseObjectives', 'co_courseObjectives');
            $qb->andWhere($qb->expr()->in('co_courseObjectives.id', ':courseObjectives'));
            $qb->setParameter(':courseObjectives', $ids);
        }

        unset($criteria['schools']);
        unset($criteria['courses']);
        unset($criteria['sessions']);
        unset($criteria['sessionTypes']);
        unset($criteria['programs']);
        unset($criteria['instructors']);
        unset($criteria['instructorGroups']);
        unset($criteria['learningMaterials']);
        unset($criteria['competencies']);
        unset($criteria['meshDescriptors']);
        unset($criteria['aamcResourceTypes']);
        unset($criteria['programYears']);
        unset($criteria['programYearObjectives']);
        unset($criteria['sessionObjectives']);
        unset($criteria['courseObjectives']);

        if ($criteria !== []) {
            foreach ($criteria as $key => $value) {
                $values = is_array($value) ? $value : [$value];
                $qb->andWhere($qb->expr()->in("t.{$key}", ":{$key}"));
                $qb->setParameter(":{$key}", $values);
            }
        }

        if (empty($orderBy)) {
            $orderBy = ['id' => 'ASC'];
        }

        if (is_array($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy('t.' . $sort, $order);
            }
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb;
    }

    public function import(array $data, string $type, array $referenceMap): array
    {
        return match ($type) {
            'term' => $this->importTerms($data, $type, $referenceMap),
            'term_x_aamc_resource_type' => $this->importTermsResourceTypeMapping($data, $referenceMap),
        };
    }

    protected function importTerms(array $data, string $type, array $referenceMap): array
    {
        // `term_id`,`title`,`parent_term_id`, `description`, `vocabulary_id`, `active`
        $entity = new Term();
        $entity->setId($data[0]);
        $entity->setTitle($data[1]);
        if (! empty($data[2])) {
            $entity->setParent($referenceMap[$type . $data[2]]);
        }
        $entity->setDescription($data[3]);
        $entity->setVocabulary($referenceMap['vocabulary' . $data[4]]);
        $entity->setActive((bool) $data[5]);
        $this->update($entity, true, true);
        $referenceMap[$type . $entity->getId()] = $entity;
        return $referenceMap;
    }

    protected function importTermsResourceTypeMapping(array $data, array $referenceMap): array
    {
        // `term_id`,`resource_type_id`
        /* @var TermInterface $entity */
        $entity = $referenceMap['term' . $data[0]];
        $resourceType = $referenceMap['aamc_resource_type' . $data[1]];
        $entity->addAamcResourceType($resourceType);
        $this->update($entity, true, true);
        return $referenceMap;
    }
}
