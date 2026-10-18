<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expenseCategories = [
            ['name' => 'Makanan', 'icon' => 'utensils', 'color' => 'amber'],
            ['name' => 'Transportasi', 'icon' => 'truck', 'color' => 'blue'],
            ['name' => 'Tempat Tinggal', 'icon' => 'home', 'color' => 'indigo'],
            ['name' => 'Tagihan', 'icon' => 'receipt', 'color' => 'rose'],
            ['name' => 'Kesehatan', 'icon' => 'heart', 'color' => 'emerald'],
            ['name' => 'Belanja', 'icon' => 'shopping-bag', 'color' => 'purple'],
            ['name' => 'Hiburan', 'icon' => 'ticket', 'color' => 'fuchsia'],
            ['name' => 'Pendidikan', 'icon' => 'academic-cap', 'color' => 'cyan'],
            ['name' => 'Keluarga', 'icon' => 'users', 'color' => 'orange'],
            ['name' => 'Biaya Layanan', 'icon' => 'credit-card', 'color' => 'slate'],
            ['name' => 'Lainnya', 'icon' => 'ellipsis-horizontal-circle', 'color' => 'zinc'],
        ];

        foreach ($expenseCategories as $category) {
            Category::firstOrCreate(
                [
                    'user_id' => null,
                    'name' => $category['name'],
                    'type' => CategoryType::Expense,
                    'is_system' => true,
                ],
                [
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'status' => CategoryStatus::Active,
                ]
            );
        }

        $incomeCategories = [
            ['name' => 'Gaji', 'icon' => 'banknotes', 'color' => 'emerald'],
            ['name' => 'Freelance', 'icon' => 'briefcase', 'color' => 'blue'],
            ['name' => 'Bisnis', 'icon' => 'building-office', 'color' => 'indigo'],
            ['name' => 'Bunga/Dividen', 'icon' => 'chart-bar', 'color' => 'teal'],
            ['name' => 'Hadiah', 'icon' => 'gift', 'color' => 'pink'],
            ['name' => 'Pengembalian Dana', 'icon' => 'arrow-path', 'color' => 'amber'],
            ['name' => 'Lainnya', 'icon' => 'ellipsis-horizontal-circle', 'color' => 'zinc'],
        ];

        foreach ($incomeCategories as $category) {
            Category::firstOrCreate(
                [
                    'user_id' => null,
                    'name' => $category['name'],
                    'type' => CategoryType::Income,
                    'is_system' => true,
                ],
                [
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'status' => CategoryStatus::Active,
                ]
            );
        }
    }
}
