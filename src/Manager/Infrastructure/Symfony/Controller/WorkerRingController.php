<?php

namespace App\Manager\Infrastructure\Symfony\Controller;

use App\Manager\Application\Command\Worker\Register\Presenter\JsonRegisterWorkerNodePresenter;
use App\Manager\Application\Command\Worker\Register\RegisterWorkerNodeCommandHandler;
use App\Manager\Application\Command\Worker\Register\RegisterWorkerNodeRequest;
use App\Shared\Infrastructure\Symfony\Controller\AbstractApiController;
use BaptisteContreras\SymfonyRequestParamBundle\Configuration\AutoProvideRequestDto;
use BaptisteContreras\SymfonyRequestParamBundle\Configuration\DtoRequestParam;
use BaptisteContreras\SymfonyRequestParamBundle\Model\SourceType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ring', name: 'ring_')]
class WorkerRingController extends AbstractApiController
{
    #[Route(path: '/join', name: 'post')]
    #[AutoProvideRequestDto]
    public function join(
        #[DtoRequestParam(sourceType: SourceType::JSON, validateDto: false)] RegisterWorkerNodeRequest $registerRequest,
        RegisterWorkerNodeCommandHandler $registerCommandHandler
    ): Response {
        $presenter = new JsonRegisterWorkerNodePresenter();

        $registerCommandHandler($registerRequest, $presenter);

        return $this->buildJsonResponse($presenter);
    }
}
