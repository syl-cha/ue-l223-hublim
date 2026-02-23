<?php

// https://symfony.com/doc/current/reference/forms/types/enum.html#example-usage
// https://php.watch/versions/8.1/enums#enum-BackedEnum
namespace App\Enum;

enum CardState: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case DONE = 'done';
    case ARCHIVED = 'archived';
}