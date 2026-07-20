<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\HandlerResponseDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumOutgoingText;
use App\Domain\Entities\Session\State\EnumState;

final readonly class AddMedicamentSelectedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
    ) {
    }

    public function handle(int $chatId, ?string $text, ?string $payload): HandlerResponseDTO
    {
        $buttons = [];
        $medicaments = $this->medicamentRepository->findByChatId($chatId);
        foreach ($medicaments as $medicament) {
            $buttons[] = new Button(
                $medicament->getName(),
                EnumState::CHANGE_MEDICAMENT_NAME_SELECTED,
            );
        }

        return new HandlerResponseDTO(EnumOutgoingText::CHOOSE_MEDICAMENT, $buttons);
    }
}
