<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\HandlerResponseDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageButton;
use App\Domain\Entities\Message\EnumOutgoingText;
use App\Domain\Entities\Session\State\EnumSessionState;
use App\Domain\Exceptions\BaseDomainException;
use App\Domain\Exceptions\InvalidValueException;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class ChangeNameMedicamentEnteredStateHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
    ) {
    }

    /**
     * @throws BaseDomainException
     */
    public function handle(?string $text, ?EnumMessageButton $clickedButton, ?string $payload): HandlerResponseDTO
    {
        if (is_null($payload)) {
            throw new InvalidValueException(EnumOutgoingText::MEDICAMENT_EMPTY_NAME_ERROR->value);
        }

        $medicamentId = intval($payload);
        $medicament = $this->medicamentRepository->findById($medicamentId);

        if (is_null($medicament)) {
            throw new NotFoundEntityException(EnumOutgoingText::MEDICAMENT_NOT_FOUND->value);
        }

        $medicament->setName($text);
        $this->medicamentRepository->save($medicament);

        return new HandlerResponseDTO(
            EnumOutgoingText::MEDICAMENT_RENAMED_SUCCESS,
            [EnumMessageButton::ADD_MEDICAMENT_BUTTON],
            EnumSessionState::MENU,
        );
    }
}
