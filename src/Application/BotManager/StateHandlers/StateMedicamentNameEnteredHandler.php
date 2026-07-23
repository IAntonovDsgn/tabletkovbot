<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\StateHandlerDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumOutgoingText;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\SendToClient\InvalidValueException;

final readonly class StateMedicamentNameEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
    ) {
    }

    public function handle(int $chatId, ?string $text, ?string $payload, ?string $clickedButtonTitle): StateHandlerDTO
    {
        if (is_null($text)) {
            throw new InvalidValueException(EnumOutgoingText::MEDICAMENT_EMPTY_NAME_ERROR->value);
        }

        $medicaments = $this->medicamentRepository->findByChatId($chatId);
        $lastMedicamentId = array_reduce($medicaments, function ($carry, $item) {
            if ($carry === null || $item->getId() > $carry->getId()) {
                return $item;
            }
            return $carry;
        });

        $medicament = new Medicament($text, $chatId, id: ++$lastMedicamentId);
        $this->medicamentRepository->save($medicament);
        return new StateHandlerDTO(
            EnumOutgoingText::ENTER_NOTIFICATION_TIME,
            [
                new Button(Button::MENU, EnumState::MENU)
            ],
            $lastMedicamentId
        );
    }
}
