<?php

namespace App\Enum;

/**
 * Énumération représentant le statut d'un utilisateur au sein de l'université.
 *
 * - `TEACHER` : le statut pour les enseignants
 * - `STUDENT` : le statut pour les étudiants
 * - `STAFF` : le statut pour le personnel administratif
 *
 */
enum StatusLabel: string
{
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case STAFF = 'staff';
}
