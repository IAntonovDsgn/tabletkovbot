<?php

declare(strict_types=1);

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\Exceptions\InvalidValueException;
use App\Application\BotManager\StateHandlerInterface;
use App\Application\BotManager\StateHandlerResponseDTO;
use App\Domain\Entities\Medicament\Medicament;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageText;
use App\Domain\Entities\Message\MessageButton;
use App\Domain\Entities\Session\Session;
use App\Domain\Entities\Session\State\EnumState;

final readonly class StateMedicamentNameEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
    ) {}

    /**
     * @throws InvalidValueException
     */
    public function handle(
        Session $session,
        ?string $messageText,
        ?string $buttonPayload
    ): StateHandlerResponseDTO {
        if (is_null($messageText)) {
            throw new InvalidValueException(EnumMessageText::MEDICAMENT_EMPTY_NAME_ERROR->value);
        }

        $medicament = Medicament::create($messageText, $session->getChatId());
        $medicamentId = $this->medicamentRepository->insert($medicament);

        return new StateHandlerResponseDTO(
            EnumMessageText::ENTER_TIME,
            [
                new MessageButton(MessageButton::MENU, EnumState::MENU),
            ],
            (string) $medicamentId
        );
    }
}
