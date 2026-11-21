<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Product::query()->exists()) {
            return;
        }

        $products = [
            ['U-01', 'Lotus Pond Urli - Mini', 'urli', 1299, ['Bestseller', 'Gift-ready']],
            ['U-02', 'Blooming Lotus Candle', 'urli', 899, ['Floral', 'Hand-poured']],
            ['U-03', 'Hibiscus Bunch Urli', 'urli', 1599, ['Statement piece', 'Floral']],
            ['U-04', 'Floating Lotus Urli', 'urli', 1199, ['Bestseller', 'Gift-ready']],
            ['U-05', 'Royal Peacock Diya Set', 'urli', 1499, ['Festive', 'Set of 2']],
            ['U-06', 'Sunflower Diya Set', 'urli', 1099, ['Festive', 'Set of 2']],
            ['U-07', 'Coral Multi-Wick Urli Trio', 'urli', 1899, ['Statement piece', 'Set of 3']],
            ['U-08', 'Elephant Urli Candle Pair', 'urli', 1399, ['Gift-ready', 'Set of 2']],
            ['U-09', 'Blossom Urli Candle', 'urli', 1199, ['Floral', 'Hand-poured']],
            ['U-10', 'Glitter Lotus Urli', 'urli', 1299, ['Bestseller', 'Festive']],
            ['U-11', 'Lotus Pond Boat Urli', 'urli', 1499, ['Statement piece', 'Gift-ready']],
            ['U-12', 'Royal Lotus Pond Urli Set', 'urli', 1999, ['Premium', 'Gift-ready']],
            ['U-13', 'Lakshmi Charan Urli', 'urli', 1199, ['Festive', 'Hand-poured']],
            ['U-14', 'Dry Fruit Urli Candle', 'urli', 1399, ['Festive', 'Gift-ready']],
            ['S-01', "Baby's Breath Jar Candle", 'soy', 649, ['Soy wax', 'Bestseller']],
            ['S-02', 'Red Peony Jar Candle', 'soy', 749, ['Floral', 'Soy wax']],
            ['S-03', 'Pink Tulip Bouquet Candle', 'soy', 899, ['Floral', 'Gift-ready']],
            ['S-04', 'Daisy Trio Candle Set', 'soy', 999, ['Set of 3', 'Soy wax']],
            ['S-05', 'White Peony Bud Candles (Set)', 'soy', 799, ['Floral', 'Set of 2']],
            ['S-06', 'Embrace Couple Candle', 'soy', 899, ['Gift-ready', 'Hand-poured']],
            ['S-07', 'Pink Peony Candle Set', 'soy', 1099, ['Floral', 'Gift-ready']],
            ['S-08', 'Classic Colour Jar Candles', 'soy', 599, ['Custom colour', 'Soy wax']],
            ['S-09', 'Blush Pink Votive Candles', 'soy', 699, ['Set of 4', 'Soy wax']],
            ['S-10', 'Bubble Cube Candle', 'soy', 499, ['Modern', 'Bestseller']],
            ['S-11', 'Heart Wax Melts & Candle', 'soy', 749, ['Gift-ready', 'Soy wax']],
            ['S-12', 'Gold Leaf Votive Candles', 'soy', 799, ['Set of 4', 'Festive']],
            ['S-13', 'Rose Bouquet Ball Candles', 'soy', 999, ['Floral', 'Gift-ready']],
            ['S-14', 'Lilac Peony Jar Candle', 'soy', 749, ['Floral', 'Soy wax']],
            ['S-15', 'Heart Blossom Candles', 'soy', 899, ['Set of 2', 'Gift-ready']],
            ['S-16', 'Daisy Gift Box Candles', 'soy', 1199, ['Gift-ready', 'Set of 4']],
            ['S-17', 'Woven Heart Candles', 'soy', 899, ['Hand-poured', 'Gift-ready']],
        ];
        $images = [
            'photo-1603006905003-be475563bc59',
            'photo-1602874801007-bd458bb1b8b6',
            'photo-1602523961358-f9f03dd557db',
            'photo-1608571423902-eed4a5ad8108',
        ];

        foreach ($products as $index => [$code, $name, $category, $price, $tags]) {
            Product::query()->create([
                'id' => Str::lower($code),
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'price' => $price,
                'stock_quantity' => 0,
                'tags' => $tags,
                'image_url' => 'https://images.unsplash.com/'.$images[$index % count($images)].'?auto=format&fit=crop&w=960&q=85',
            ]);
        }
    }
}
