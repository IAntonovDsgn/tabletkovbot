<?php

namespace App\Application\BotManager\StateHandlers;

use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\EnumMessageButtonType;
use App\Domain\Entities\Message\EnumOutgoingMessageKey;
use App\Domain\Entities\Session\State\EnumSessionState;
use App\Domain\Entities\Session\State\StateHandlerInterface;
use App\Domain\Entities\Session\State\StateHandlerResponseDTO;
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
    public function handle(?string $text, ?EnumMessageButtonType $clickedButton, ?string $payload): StateHandlerResponseDTO
    {
        if (is_null($payload)) {
            throw new InvalidValueException(EnumOutgoingMessageKey::MEDICAMENT_EMPTY_NAME_ERROR->value);
        }
        $medicamentId = intval($payload);
        $medicament = $this->medicamentRepository->findById($medicamentId);
        if (is_null($medicament)) {
            throw new NotFoundEntityException(EnumOutgoingMessageKey::MEDICAMENT_NOT_FOUND->value);
        }
        $medicament->setName($text);
        $this->medicamentRepository->save($medicament);
        return new StateHandlerResponseDTO(
            EnumOutgoingMessageKey::MEDICAMENT_RENAMED_SUCCESS->value,
            [EnumMessageButtonType::ADD_MEDICAMENT_BUTTON],
            EnumSessionState::MENU,
        );
    }
}
