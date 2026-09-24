<?php

namespace App\Enums;

enum EmployeeDocumentType: string
{
    case OfferLetter = 'offer_letter';
    case EmploymentAgreement = 'employment_agreement';
    case Nda = 'nda';
    case IpAssignment = 'ip_assignment';
    case IdDocument = 'id_document';
    case Other = 'other';

    public function label(): string
    {
        return config('hr.document_types.'.$this->value, $this->value);
    }

    public function requiresExpiry(): bool
    {
        return in_array($this, [self::IdDocument, self::Other], true);
    }
}
