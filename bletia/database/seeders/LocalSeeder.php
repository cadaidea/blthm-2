<?php

namespace Database\Seeders;

use App\Models\Local;
use Illuminate\Database\Seeder;

class LocalSeeder extends Seeder
{
    public function run(): void
    {
        $locales = [
            ['nombre' => 'Local 1', 'tipo' => 'local'],
            ['nombre' => 'Local 2', 'tipo' => 'local'],
            ['nombre' => 'Bodega A', 'tipo' => 'bodega'],
            ['nombre' => 'Bodega B', 'tipo' => 'bodega'],
        ];
        foreach ($locales as $l) {
            Local::firstOrCreate(['nombre' => $l['nombre']], $l);
        }
    }
}
