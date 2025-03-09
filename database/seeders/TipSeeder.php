<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tips')->insert([
            [
                'title' => 'Cara menyimpan uang yang baik',
                'thumbnail' => 'nabung.jpg',
                'url' => 'https://www.dana.id/corporate/newsroom/cara-membuat-akun-dana',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'title' => 'Cara berinvestasi Emas',
                'thumbnail' => 'emas.jpg',
                'url' => 'https://planblife.bni-life.co.id/artikel/kumpulan-tips-investasi-emas-yang-mudah-dan-aman-bagi-pemula-jygub',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'title' => 'Investasi saham untuk Pemula',
                'thumbnail' => 'saham.jpg',
                'url' => 'https://www.mncsekuritas.id/pages/3-tips-investasi-saham-untuk-pemula/',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
