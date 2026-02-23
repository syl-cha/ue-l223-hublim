<?php

namespace App\Enum;

enum StatusLabel: string
{
  case TEACHER = 'teacher';
  case STUDENT = 'student';
  case STAFF = 'staff';
}