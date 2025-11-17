<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // Danh sách đơn
    public function index(Request $request)
    {
        $query = Order::query();

        // Tìm kiếm theo mã đơn (code = rút gọn _id) hoặc địa chỉ giao hàng
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('_id', 'like', '%' . $search . '%')
                  ->orWhere('shippingAddress.address', 'like', '%' . $search . '%');
            });
        }

        // Lọc theo trạng thái (awaiting_payment, processing, ...)
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $orders = $query
            ->orderBy('createdAt', 'desc')
            ->paginate(15);

        $statusOptions = Order::getStatusOptions();

        return view('orders.index', compact('orders', 'statusOptions'));
    }

    // Chi tiết đơn + timeline
    public function show(string $id)
    {
        // laravel-mongodb tự convert string sang ObjectId khi findOrFail
        $order = Order::with('items')->findOrFail($id);

        $steps = Order::getTimelineSteps();

        return view('orders.show', compact('order', 'steps'));
    }
}
