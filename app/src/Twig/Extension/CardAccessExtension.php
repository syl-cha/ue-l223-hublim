<?php

namespace App\Twig\Extension;

use App\Entity\Card;
use App\Enum\CardState;
use App\Enum\MessageState;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CardAccessExtension extends AbstractExtension
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('can_view_card', [$this, 'canViewCard']),
        ];
    }

    public function canViewCard(Card $card, ?UserInterface $user): bool
    {
        // L'auteur a toujours accès à son annonce
        if ($user instanceof \App\Entity\User && $card->getUser() === $user) {
            return true;
        }

        // L'admin a toujours accès à tout
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // --- Logique de Modération ---
        // Si l'annonce est elle-même signalée, elle est masquée
        if ($card->getState() === CardState::FLAGGED) {
            return false;
        }

        // Si l'annonce contient au moins un message signalé, elle est masquée
        foreach ($card->getMessages() as $msg) {
            if ($msg->getState() === MessageState::FLAGGED) {
                return false;
            }
        }
        // ------------------------------

        // Si aucune restriction n'est définie sur l'annonce, tout le monde y a accès
        if ($card->getTargetStatus()->isEmpty() && $card->getTargetStudyFields()->isEmpty()) {
            return true;
        }

        // Si des restrictions sont définies et que l'utilisateur n'est pas connecté ou n'est pas du bon type, accès refusé
        if (!$user instanceof \App\Entity\User) {
            return false;
        }

        $hasStatusRestriction = !$card->getTargetStatus()->isEmpty();
        $hasStudyFieldRestriction = !$card->getTargetStudyFields()->isEmpty();

        $statusMatch = true;
        if ($hasStatusRestriction) {
            $statusMatch = $user->getStatus() !== null && $card->getTargetStatus()->contains($user->getStatus());
        }

        $studyFieldMatch = true;
        if ($hasStudyFieldRestriction) {
            $studyFieldMatch = $user->getStudyField() !== null && $card->getTargetStudyFields()->contains($user->getStudyField());
        }

        // L'utilisateur doit satisfaire TOUTES les restrictions définies (ET logique)
        return $statusMatch && $studyFieldMatch;
    }
}
