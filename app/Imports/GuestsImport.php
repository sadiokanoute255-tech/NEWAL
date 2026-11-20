<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Sert uniquement à lire le fichier avec la ligne d'en-tête ; le traitement est dans GuestImportController. */
class GuestsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        //
    }
}
