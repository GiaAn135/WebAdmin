<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use MongoDB\BSON\ObjectId;

class ProductsFromJsonSeeder extends Seeder
{
    public function run(): void
    {
        $path = 'mongo/test.DB.products.json';

        if (!Storage::disk('local')->exists($path)) {
            $this->command->error("Không thấy file {$path}");
            return;
        }

        $json = Storage::disk('local')->get($path);
        $data = json_decode($json, true);

        if (!is_array($data)) {
            $this->command->error("File products JSON không đúng format array");
            return;
        }

        foreach ($data as $item) {
            if (!isset($item['_id']['$oid'])) {
                continue;
            }

            $id = new ObjectId($item['_id']['$oid']);

            Product::updateOrCreate(
                ['_id' => $id],
                [
                    'name'        => $item['name'] ?? null,
                    'description' => $item['description'] ?? null,
                    'images'      => $item['images'] ?? [],
                    'price'       => $item['price'] ?? 0,
                    'category'    => $item['category'] ?? null,
                    'brand'       => $item['brand'] ?? null,
                    'status'      => $item['status'] ?? null,
                    'videoUrl'    => $item['videoUrl'] ?? null,
                    'variants'    => $item['variants'] ?? [],
                ]
            );
        }

        $this->command->info('Import products xong!');
    }
}
