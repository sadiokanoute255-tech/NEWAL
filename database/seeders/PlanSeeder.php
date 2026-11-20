<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /** Les cinq offres du cahier des charges (§32). Les tarifs se règlent ensuite dans l'administration. */
    public function run(): void
    {
        $offres = [
            ['Essentiel', 50],
            ['Standard', 150],
            ['Premium', 300],
            ['Prestige', 500],
            ['Entreprise', 1000],
        ];

        foreach ($offres as $i => [$nom, $max]) {
            Plan::firstOrCreate(['name' => $nom], [
                'max_guests' => $max,
                'price' => 0,
                'currency' => 'XOF',
                'billing_period' => 'unique',
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
        }
    }
}