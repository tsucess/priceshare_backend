<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class CategoryTagSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Food & Groceries',         'emoji' => '🌾', 'color' => '#00e676', 'sort_order' => 1],
            ['name' => 'Meat & Poultry',            'emoji' => '🐔', 'color' => '#ff6e40', 'sort_order' => 2],
            ['name' => 'Vegetables & Fruits',       'emoji' => '🥦', 'color' => '#69f0ae', 'sort_order' => 3],
            ['name' => 'Fuel & Energy',             'emoji' => '⛽', 'color' => '#ffd600', 'sort_order' => 4],
            ['name' => 'Medications & Healthcare',  'emoji' => '💊', 'color' => '#00b0ff', 'sort_order' => 5],
            ['name' => 'Building Materials',        'emoji' => '🧱', 'color' => '#a0522d', 'sort_order' => 6],
            ['name' => 'Clothing & Fashion',        'emoji' => '👗', 'color' => '#e040fb', 'sort_order' => 7],
            ['name' => 'Electronics',               'emoji' => '📱', 'color' => '#40c4ff', 'sort_order' => 8],
            ['name' => 'Transport',                 'emoji' => '🚌', 'color' => '#ff4d6d', 'sort_order' => 9],
            ['name' => 'Household Items',           'emoji' => '🧴', 'color' => '#80deea', 'sort_order' => 10],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat['name']], array_merge($cat, ['is_active' => true]));
        }

        $tags = [
            ['name' => 'Price Surge',   'color' => '#ff4d6d'],
            ['name' => 'Price Drop',    'color' => '#00e676'],
            ['name' => 'Shortage',      'color' => '#ffd600'],
            ['name' => 'New Arrival',   'color' => '#00b0ff'],
            ['name' => 'Bulk Discount', 'color' => '#a855f7'],
            ['name' => 'Seasonal',      'color' => '#ff6e40'],
            ['name' => 'Imported',      'color' => '#40c4ff'],
            ['name' => 'Locally Made',  'color' => '#69f0ae'],
            ['name' => 'Flash Sale',    'color' => '#e040fb'],
            ['name' => 'Urgent',        'color' => '#ff4d6d'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['name' => $tag['name']], array_merge($tag, ['is_active' => true]));
        }

        $this->command->info('Categories and Tags seeded successfully.');
    }
}
