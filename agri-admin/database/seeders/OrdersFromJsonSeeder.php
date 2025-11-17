<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Order;
use MongoDB\BSON\ObjectId;
use Carbon\Carbon;

class OrdersFromJsonSeeder extends Seeder
{
    public function run(): void
    {
        $path = 'mongo/test.DB.orders.json';

        if (!Storage::disk('local')->exists($path)) {
            $this->command->error("Không thấy file {$path}");
            return;
        }

        $json = Storage::disk('local')->get($path);
        $data = json_decode($json, true);

        if (!$data) {
            $this->command->error("File orders JSON rỗng hoặc lỗi");
            return;
        }

        // Có thể là 1 object hoặc array
        $orders = array_keys($data) === range(0, count($data) - 1)
            ? $data
            : [$data];

        foreach ($orders as $item) {
            if (!isset($item['_id']['$oid'])) {
                continue;
            }

            $id = new ObjectId($item['_id']['$oid']);

            Order::updateOrCreate(
                ['_id' => $id],
                [
                    'user'                 => $item['user']['$oid']      ?? null,
                    'manager'              => $item['manager']['$oid']   ?? null,
                    'store'                => $item['store']['$oid']     ?? null,
                    'orderItems'           => $item['orderItems']        ?? [],
                    'shippingAddress'      => $item['shippingAddress']   ?? [],
                    'paymentMethod'        => $item['paymentMethod']     ?? null,
                    'shippingLocation'     => $item['shippingLocation']  ?? [],
                    'subtotal'             => $item['subtotal']          ?? 0,
                    'shippingFee'          => $item['shippingFee']       ?? 0,
                    'vatFee'               => $item['vatFee']            ?? 0,
                    'totalPrice'           => $item['totalPrice']        ?? 0,
                    'commission'           => $item['commission']        ?? 0,
                    'amountPayableToStore' => $item['amountPayableToStore'] ?? 0,
                    'status'               => $item['status']            ?? null,
                    'managerPaymentStatus' => $item['managerPaymentStatus'] ?? null,
                    'pickupPhotoUrl'       => $item['pickupPhotoUrl']    ?? null,
                    'deliveryPhotoUrl'     => $item['deliveryPhotoUrl']  ?? null,
                    'createdAt'            => isset($item['createdAt']['$date'])
                        ? Carbon::parse($item['createdAt']['$date'])
                        : now(),
                    'updatedAt'            => isset($item['updatedAt']['$date'])
                        ? Carbon::parse($item['updatedAt']['$date'])
                        : now(),
                ]
            );
        }

        $this->command->info('Import orders xong!');
    }
}
