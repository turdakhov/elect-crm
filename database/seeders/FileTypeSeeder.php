<?php

namespace Database\Seeders;

use App\Models\FileType;
use Illuminate\Database\Seeder;

class FileTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fileTypes = [
            ['name' => 'pdf', 'description' => 'PDF документ'],
            ['name' => 'xlsx', 'description' => 'Excel таблица'],
            ['name' => 'картинка', 'description' => 'Изображение (jpg, png, gif и т.д.)'],
        ];

        foreach ($fileTypes as $fileType) {
            FileType::firstOrCreate(
                ['name' => $fileType['name']],
                ['description' => $fileType['description']]
            );
        }
    }
}
