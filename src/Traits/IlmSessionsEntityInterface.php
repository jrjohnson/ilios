<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\IlmSessionInterface;

/**
 * Interface IlmSessionsEntityInterface
 */
interface IlmSessionsEntityInterface
{
    /**
     * @param Collection $ilmSessions
     */
    public function setIlmSessions(Collection $ilmSessions);

    /**
     * @param IlmSessionInterface $ilmSession
     */
    public function addIlmSession(IlmSessionInterface $ilmSession);

    /**
     * @param IlmSessionInterface $ilmSession
     */
    public function removeIlmSession(IlmSessionInterface $ilmSession);

    /**
    * @return IlmSessionInterface[]|ArrayCollection
    */
    public function getIlmSessions();
}
