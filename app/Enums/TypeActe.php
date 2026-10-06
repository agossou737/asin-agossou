<?php

namespace App\Enums;

enum TypeActe: string
{
    case Naissance = 'acte_naissance';
    case CasierJudiciaire = 'casier_judiciaire';
    case CertificatResidence = 'certificat_residence';

    public function label(): string
    {
        return match ($this) {
            self::Naissance => 'Acte de naissance',
            self::CasierJudiciaire => 'Casier judiciaire',
            self::CertificatResidence => 'Certificat de résidence',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
