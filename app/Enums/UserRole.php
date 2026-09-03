<?php

namespace App\Enums;

enum UserRole: string
{
    case CUSTOMER = 'customer';
    case RESTAURANT_OWNER = 'restaurant_owner';
    case ADMIN = 'admin';
}
