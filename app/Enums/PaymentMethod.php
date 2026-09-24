<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Upi = 'upi';
    case Cash = 'cash';
    case Cheque = 'cheque';
    case Card = 'card';
    case Other = 'other';

    public function label(): string
    {
        return config('finance.payment_methods.'.$this->value, ucfirst(str_replace('_', ' ', $this->value)));
    }
}
