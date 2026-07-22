<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\HandlerOutputDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumOutgoingText;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\NotSendToClient\NotFoundEntityException;

final readonly class StateChangeMedicamentSelectedHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
    ) {
    }

    public function handle(int $chatId, ?string $text, ?string $payload, ?string $clickedButtonTitle): HandlerOutputDTO
    {
        $buttons = [];
        $medicaments = $this->medicamentRepository->findByChatId($chatId);

        if (empty($medicaments)) {
            throw new NotFoundEntityException(EnumOutgoingText::MEDICAMENT_NOT_FOUND->value);
        }

        foreach ($medicaments as $medicament) {
            $buttons[] = new Button(
                $medicament->getName(),
                EnumState::CHANGE_MEDICAMENT_NAME_SELECTED,
            );
        }

        return new HandlerOutputDTO(EnumOutgoingText::CHOOSE_MEDICAMENT, $buttons);
    }
}
