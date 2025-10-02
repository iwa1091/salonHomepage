<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BeforeAfterCase;
use App\Models\GalleryImage;
use App\Models\Review;

use Database\Factories\BeforeAfterCaseFactory;
use Database\Factories\GalleryImageFactory;
use Database\Factories\ReviewFactory;

class SalonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // データをクリアして重複を避ける
        BeforeAfterCase::truncate();
        GalleryImage::truncate();
        Review::truncate();
        
        // 掲載するデータをファクトリを使って生成
        BeforeAfterCase::factory(3)->create();
        GalleryImage::factory(6)->create();
        Review::factory(6)->create();

        // 特定のデータを個別に追加したい場合は、以下のように記述
        Review::create([
            'name' => 'M.S様',
            'age' => '20代',
            'rating' => 5,
            'comment' => '初めてのまつげエクステでしたが、丁寧にカウンセリングしていただき、理想通りの仕上がりになりました。自然で上品な仕上がりで、友人からも好評です。',
            'service' => 'ナチュラルエクステ',
            'date' => '2024年1月',
        ]);
        
        // その他の特定のデータも同様に追加...
    }
}