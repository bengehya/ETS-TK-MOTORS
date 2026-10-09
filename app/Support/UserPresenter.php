<?php

namespace App\Support;

use App\Models\User;

/**
 * Identité affichable d'une personne ayant réalisé une action métier
 * (arrivage, ajustement, future vente, future opération).
 *
 * Le futur module Ventes doit enregistrer le vendeur authentifié — patron
 * ou employé — et réutiliser cette forme, avec la date et l'heure de la vente.
 * La civilité n'est jamais déduite de l'adresse e-mail.
 */
class UserPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function identity(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->displayName(),
            'photo_url' => $user->profilePhotoUrl(),
            'civility' => $user->civility?->value,
            'civility_label' => $user->civility?->label(),
        ];
    }

    public static function welcome(User $user): string
    {
        $brand = (string) config('tkmotors.name');
        $name = $user->displayName();

        if ($user->civility !== null) {
            return "Bienvenue dans {$brand}, {$user->civility->label()} {$name}";
        }

        return "Bienvenue dans {$brand}, {$name}";
    }
}
