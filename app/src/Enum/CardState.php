<?php

// https://symfony.com/doc/current/reference/forms/types/enum.html#example-usage
// https://php.watch/versions/8.1/enums#enum-BackedEnum
namespace App\Enum;

/**
 * Énumération représentant l'état d'une annonce (carte).
 *
 * - `DRAFT` : l'annonce est à l'état de brouillon
 * - `PUBLISHED` : l'annonce est publiée et visible
 * - `DONE` : l'annonce est marquée comme terminée ou résolue
 * - `ARCHIVED` : l'annonce est archivée
 *
 */
enum CardState: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case DONE = 'done';
    case ARCHIVED = 'archived';
}
