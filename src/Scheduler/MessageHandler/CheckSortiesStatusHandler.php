<?php

namespace App\Scheduler\MessageHandler;

use App\Repository\SortieRepository;
use App\Scheduler\Message\CheckSortiesStatus;
use App\Service\SortieService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CheckSortiesStatusHandler
{
    private SortieService $sortieService;

    public function __construct(SortieRepository $sortieRepository, SortieService $sortieService)
    {
        $this->sortieService = $sortieService;
    }
    public function __invoke(CheckSortiesStatus $message)
    {
        $this->sortieService->updateSortieEtats();
    }
}
