<?php


namespace Model;


use System\Core\Emerald_enum;

class Transaction_type extends Emerald_enum
{
    const TRANSACTION_TYPE_REFILL = 1;
    const TRANSACTION_TYPE_WRITE_OFF = 2;
}