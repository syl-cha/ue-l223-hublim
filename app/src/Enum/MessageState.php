<?php

namespace App\Enum;

/**
 * Énumération représentant l'état d'une annonce (carte).
 *
 * - `PUBLISHED` : le message est publiée et visible
 * - `FLAGGED` : le message est signalé comme contenant un propos licencieux
 * - `ARCHIVED` : le message est archivé
 *
 */
enum MessageState: string
{
    case PUBLISHED = 'published';
    case FLAGGED = 'flagged';
    case ARCHIVED = 'archived';
}
