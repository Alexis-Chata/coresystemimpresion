<?php

namespace Database\Seeders;

use App\Models\Impresora;
use Illuminate\Database\Seeder;

class ImpresoraSeeder extends Seeder
{
    public function run(): void
    {
        $impresoras = ['POS-80C-1', 'POS-80C-2', 'EPSON-TM-U220-Receipt', 'EPSON-TM-T88V-Receipt'];
        foreach ($impresoras as $nombre) {
            Impresora::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
