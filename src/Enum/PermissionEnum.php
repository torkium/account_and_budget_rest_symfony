<?php
namespace App\Enum;

enum PermissionEnum: int {
    case ADMIN = 30;
    case READER = 20;
    case WRITER = 10;
}