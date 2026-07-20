<?php

namespace App\Application\BotManager\StateHandlers;

use App\Application\BotManager\HandlerResponseDTO;
use App\Application\BotManager\StateHandlerInterface;
use App\Domain\Entities\Medicament\MedicamentRepositoryInterface;
use App\Domain\Entities\Message\Button\Button;
use App\Domain\Entities\Message\EnumOutgoingText;
use App\Domain\Entities\Session\State\EnumState;
use App\Domain\Exceptions\BaseDomainException;
use App\Domain\Exceptions\InvalidValueException;
use App\Domain\Exceptions\NotFoundEntityException;

final readonly class ChangeNameMedicamentEnteredHandler implements StateHandlerInterface
{
    public function __construct(
        private MedicamentRepositoryInterface $medicamentRepository,
    ) {
    }

    /**
     * @throws BaseDomainException
     */
    public function handle(int $chatId, ?string $text, ?string $payload): HandlerResponseDTO
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
            [
                new Button(Button::MAKE_INTAKE_MARK_BUTTON_TITLE, EnumState::MAKE_INTAKE_MARK_SELECTED),
                new Button(Button::ADD_MEDICAMENT_BUTTON_TITLE, EnumState::ADD_MEDICAMENT_SELECTED),
                new Button(Button::CHANGE_MEDICAMENT_BUTTON_TITLE, EnumState::CHANGE_MEDICAMENT_SELECTED),
                new Button(Button::DELETE_MEDICAMENT_BUTTON_TITLE, EnumState::DELETE_MEDICAMENT_SELECTED),
                new Button(Button::DOWNLOAD_REPORT_BUTTON_TITLE, EnumState::DOWNLOAD_REPORT_SELECTED),
                new Button(Button::NOTIFICATIONS_BUTTON_TITLE, EnumState::NOTIFICATIONS_SELECTED),
            ]
        );
    }
}
