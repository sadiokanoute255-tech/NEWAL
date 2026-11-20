<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GuestsTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['prenom', 'nom', 'telephone', 'whatsapp', 'email', 'organisation', 'fonction', 'cercle', 'places', 'commentaire'];
    }

    public function array(): array
    {
        return [
            ['Awa', 'Ndiaye', '771234567', '771234567', 'awa@exemple.com', '', '', 'Famille', 2, 'Exemple : à supprimer'],
        ];
    }
}
