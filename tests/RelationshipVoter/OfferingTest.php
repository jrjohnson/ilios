<?php

declare(strict_types=1);

namespace App\Tests\RelationshipVoter;

use App\Classes\VoterPermissions;
use App\Entity\CourseInterface;
use App\Entity\OfferingInterface;
use App\Entity\SchoolInterface;
use App\Entity\SessionInterface;
use App\RelationshipVoter\Offering as Voter;
use App\Service\SessionUserPermissionChecker;
use Mockery as m;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class OfferingTest extends AbstractBase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->permissionChecker = m::mock(SessionUserPermissionChecker::class);
        $this->voter = new Voter($this->permissionChecker);
    }

    public function testAllowsRootFullAccess(): void
    {
        $this->checkRootEntityAccess(m::mock(OfferingInterface::class));
    }

    public function testCanView(): void
    {
        $user = $this->createMockNonRootSessionUser();
        $token = $this->createMockTokenWithMockSessionUser($user);
        $user->shouldReceive('performsNonLearnerFunction')->andReturn(true);
        $entity = m::mock(OfferingInterface::class);
        $response = $this->voter->vote($token, $entity, [VoterPermissions::VIEW]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $response, "View allowed");
    }

    public function testCanNotView(): void
    {
        $user = $this->createMockNonRootSessionUser();
        $token = $this->createMockTokenWithMockSessionUser($user);
        $user->shouldReceive('performsNonLearnerFunction')->andReturn(false);
        $entity = m::mock(OfferingInterface::class);
        $response = $this->voter->vote($token, $entity, [VoterPermissions::VIEW]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $response, "View denied");
    }

    public function testCanEdit(): void
    {
        $user = $this->createMockNonRootSessionUser();
        $token = $this->createMockTokenWithMockSessionUser($user);
        $entity = m::mock(OfferingInterface::class);
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn(1);
        $course = m::mock(CourseInterface::class);
        $course->shouldReceive('getId')->andReturn(1);
        $school = m::mock(SchoolInterface::class);
        $school->shouldReceive('getId')->andReturn(1);
        $entity->shouldReceive('getSession')->andReturn($session);
        $session->shouldReceive('getCourse')->andReturn($course);
        $course->shouldReceive('getSchool')->andReturn($school);
        $this->permissionChecker->shouldReceive('canUpdateSession')->andReturn(true);
        $response = $this->voter->vote($token, $entity, [VoterPermissions::EDIT]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $response, "Edit allowed");
    }

    public function testCanNotEdit(): void
    {
        $user = $this->createMockNonRootSessionUser();
        $token = $this->createMockTokenWithMockSessionUser($user);
        $entity = m::mock(OfferingInterface::class);
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn(1);
        $course = m::mock(CourseInterface::class);
        $course->shouldReceive('getId')->andReturn(1);
        $school = m::mock(SchoolInterface::class);
        $school->shouldReceive('getId')->andReturn(1);
        $entity->shouldReceive('getSession')->andReturn($session);
        $session->shouldReceive('getCourse')->andReturn($course);
        $course->shouldReceive('getSchool')->andReturn($school);
        $this->permissionChecker->shouldReceive('canUpdateSession')->andReturn(false);
        $response = $this->voter->vote($token, $entity, [VoterPermissions::EDIT]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $response, "Edit denied");
    }

    public function testCanDelete(): void
    {
        $user = $this->createMockNonRootSessionUser();
        $token = $this->createMockTokenWithMockSessionUser($user);
        $entity = m::mock(OfferingInterface::class);
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn(1);
        $course = m::mock(CourseInterface::class);
        $course->shouldReceive('getId')->andReturn(1);
        $school = m::mock(SchoolInterface::class);
        $school->shouldReceive('getId')->andReturn(1);
        $entity->shouldReceive('getSession')->andReturn($session);
        $session->shouldReceive('getCourse')->andReturn($course);
        $course->shouldReceive('getSchool')->andReturn($school);
        $this->permissionChecker->shouldReceive('canUpdateSession')->andReturn(true);
        $response = $this->voter->vote($token, $entity, [VoterPermissions::DELETE]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $response, "Delete allowed");
    }

    public function testCanNotDelete(): void
    {
        $user = $this->createMockNonRootSessionUser();
        $token = $this->createMockTokenWithMockSessionUser($user);
        $entity = m::mock(OfferingInterface::class);
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn(1);
        $course = m::mock(CourseInterface::class);
        $course->shouldReceive('getId')->andReturn(1);
        $school = m::mock(SchoolInterface::class);
        $school->shouldReceive('getId')->andReturn(1);
        $entity->shouldReceive('getSession')->andReturn($session);
        $session->shouldReceive('getCourse')->andReturn($course);
        $course->shouldReceive('getSchool')->andReturn($school);
        $this->permissionChecker->shouldReceive('canUpdateSession')->andReturn(false);
        $response = $this->voter->vote($token, $entity, [VoterPermissions::DELETE]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $response, "Delete denied");
    }

    public function testCanCreate(): void
    {
        $user = $this->createMockNonRootSessionUser();
        $token = $this->createMockTokenWithMockSessionUser($user);
        $entity = m::mock(OfferingInterface::class);
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn(1);
        $course = m::mock(CourseInterface::class);
        $course->shouldReceive('getId')->andReturn(1);
        $school = m::mock(SchoolInterface::class);
        $school->shouldReceive('getId')->andReturn(1);
        $entity->shouldReceive('getSession')->andReturn($session);
        $session->shouldReceive('getCourse')->andReturn($course);
        $course->shouldReceive('getSchool')->andReturn($school);
        $this->permissionChecker->shouldReceive('canUpdateSession')->andReturn(true);
        $response = $this->voter->vote($token, $entity, [VoterPermissions::CREATE]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $response, "Create allowed");
    }

    public function testCanNotCreate(): void
    {
        $user = $this->createMockNonRootSessionUser();
        $token = $this->createMockTokenWithMockSessionUser($user);
        $entity = m::mock(OfferingInterface::class);
        $session = m::mock(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn(1);
        $course = m::mock(CourseInterface::class);
        $course->shouldReceive('getId')->andReturn(1);
        $school = m::mock(SchoolInterface::class);
        $school->shouldReceive('getId')->andReturn(1);
        $entity->shouldReceive('getSession')->andReturn($session);
        $session->shouldReceive('getCourse')->andReturn($course);
        $course->shouldReceive('getSchool')->andReturn($school);
        $this->permissionChecker->shouldReceive('canUpdateSession')->andReturn(false);
        $response = $this->voter->vote($token, $entity, [VoterPermissions::CREATE]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $response, "Create denied");
    }

    public static function supportsTypeProvider(): array
    {
        return [
            [OfferingInterface::class, true],
            [self::class, false],
        ];
    }

    public static function supportsAttributesProvider(): array
    {
        return [
            [VoterPermissions::VIEW, true],
            [VoterPermissions::CREATE, true],
            [VoterPermissions::DELETE, true],
            [VoterPermissions::EDIT, true],
            [VoterPermissions::LOCK, false],
            [VoterPermissions::UNLOCK, false],
            [VoterPermissions::ROLLOVER, false],
            [VoterPermissions::CREATE_TEMPORARY_FILE, false],
            [VoterPermissions::VIEW_DRAFT_CONTENTS, false],
            [VoterPermissions::VIEW_VIRTUAL_LINK, false],
            [VoterPermissions::ARCHIVE, false],
        ];
    }
}
