<?php

namespace App\Enums;

/** The document slots on the application. */
enum DocumentType: string
{
    case Identity = 'identity';
    case CompanyRegistration = 'company_registration';
    case ProofOfFunds = 'proof_of_funds';
    case AssetsLiabilities = 'assets_liabilities';
    case BankConfirmation = 'bank_confirmation';
    case PropertyLease = 'property_lease';

    public function label(): string
    {
        return match ($this) {
            self::Identity => 'ID documents (all members, shareholders or partners)',
            self::CompanyRegistration => 'Company registration (CK1 / Certificate of Incorporation)',
            self::ProofOfFunds => 'Proof of unencumbered cash available for investment',
            self::AssetsLiabilities => 'Statement of assets and liabilities',
            self::BankConfirmation => 'Bank confirmation of balances and outstanding loans/bonds',
            self::PropertyLease => 'Property deeds or signed lease agreement (once a site is confirmed)',
        };
    }

    /** Must be uploaded before the application can be submitted. */
    public function isRequired(?string $entityType): bool
    {
        return match ($this) {
            self::Identity, self::ProofOfFunds, self::AssetsLiabilities => true,
            self::CompanyRegistration => $entityType !== null && $entityType !== 'sole_proprietorship',
            default => false,
        };
    }
}
