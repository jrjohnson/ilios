<?php

declare(strict_types=1);

namespace App\Controller\API;

use App\Repository\IngestionExceptionRepository;
use App\Service\ApiResponseBuilder;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[OA\Tag(name:'Ingestionexceptions')]
#[Route('/api/{version<v3>}/ingestionexceptions')]
class IngestionExceptions extends AbstractApiController
{
    public function __construct(IngestionExceptionRepository $repository)
    {
        parent::__construct($repository, 'ingestionexceptions');
    }

    #[Route(
        '/{id}',
        methods: ['GET']
    )]
    public function getOne(
        string $version,
        string $id,
        AuthorizationCheckerInterface $authorizationChecker,
        ApiResponseBuilder $builder,
        Request $request
    ): Response {
        return $this->handleGetOne($version, $id, $authorizationChecker, $builder, $request);
    }

    #[Route(
        methods: ['GET']
    )]
    public function getAll(
        string $version,
        Request $request,
        AuthorizationCheckerInterface $authorizationChecker,
        ApiResponseBuilder $builder
    ): Response {
        return $this->handleGetAll($version, $request, $authorizationChecker, $builder);
    }
}
