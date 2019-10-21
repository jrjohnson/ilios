<?php

namespace App\RelationshipVoter;

use App\Classes\UserEvent as Event;
use App\Classes\SessionUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Class UserEvent
 */
class UserEvent extends AbstractVoter
{
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return $subject instanceof Event && in_array($attribute, array(self::VIEW));
    }

    /**
     * @param string $attribute
     * @param Event $event
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $event, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof SessionUserInterface) {
            return false;
        }

        // root user can see all user events
        if ($user->isRoot()) {
            return true;
        }

        // if the event is published and owned by the current user
        // then it can be viewed.
        if ($event->isPublished && $user->getId() === $event->user) {
            return true;
        }

        $sessionId = $event->session;
        $courseId = $event->course;
        $schoolId = $event->school;

        // if the current user is associated with the given event
        // in a directing/administrating/instructing capacity via the event's
        // owning school/course/session context,
        // and the event is published or owned by the current user,
        // then it can be viewed.
        if (in_array($schoolId, $user->getAdministeredSchoolIds())
            || in_array($schoolId, $user->getDirectedSchoolIds())
            || in_array($schoolId, $user->getDirectedProgramSchoolIds())
            || in_array($courseId, $user->getAdministeredCourseIds())
            || in_array($courseId, $user->getDirectedCourseIds())
            || in_array($sessionId, $user->getAdministeredSessionIds())
            || in_array($sessionId, $user->getInstructedSessionIds())
        ) {
            return $event->isPublished || $user->getId() === $event->user;
        }

        return false;
    }
}
