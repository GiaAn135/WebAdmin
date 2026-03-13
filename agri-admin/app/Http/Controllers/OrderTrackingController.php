<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\OrderController;
use App\Models\Order;
use Illuminate\Http\Request;


class OrderTrackingController extends Controller
{
    public function index()
    {
        // Lấy tất cả các đơn hàng từ database (hoặc có thể thay đổi điều kiện lọc)
        $orders = Order::orderBy('created_at', 'desc')->get();

        // Trả về view với dữ liệu đơn hàng
        return view('orders.tracking', compact('orders'));
    }
}

public function show($id)
{
    $order = Order::find($id);

    // Kiểm tra nếu không tìm thấy đơn hàng
    if (!$order) {
        return redirect()->route('orders.tracking')->with('error', 'Đơn hàng không tồn tại.');
    }

    return view('orders.show', compact('order'));
}

 public function approveByAdmin($id)
    {
        $order = Order::find($id);

        // Kiểm tra nếu không tìm thấy đơn hàng
        if (!$order) {
            return redirect()->route('orders.tracking')->with('error', 'Đơn hàng không tồn tại.');
        }

        // Cập nhật trạng thái đơn hàng thành "Chờ VTNN lấy hàng"
        $order->status = 'approved_by_admin';  // Trạng thái mới
        $order->save();

        return redirect()->route('orders.tracking')->with('success', 'Đơn hàng đã được duyệt.');
    }
}

public function moveToPreparing(string $id)
{
    $order = Order::findOrFail($id);

    // Chuyển trạng thái từ "Đã duyệt → Chờ VTNN chuẩn bị hàng" -> "VTNN đã soạn hàng xong → Chờ lấy hàng"
    if ($order->status === 'approved_by_admin') {
        $order->status = 'preparing';
        $order->save();
    }

    return back()->with('success', 'Đơn hàng đã được chuẩn bị xong, chờ lấy hàng.');
}

public function moveToReadyForPickup(string $id)
{
    $order = Order::findOrFail($id);

    // Chuyển trạng thái từ "VTNN đã soạn hàng xong → Chờ lấy hàng" -> "Đã lấy hàng → Chờ thanh toán"
    if ($order->status === 'preparing') {
        $order->status = 'ready_for_pickup';
        $order->save();
    }

    return back()->with('success', 'Đơn hàng đã được lấy, chờ thanh toán.');
}

public function moveToAwaitingPayment(string $id)
{
    $order = Order::findOrFail($id);

    // Chuyển trạng thái từ "Đã lấy hàng → Chờ thanh toán" -> "Đã thanh toán → Đi giao hàng"
    if ($order->status === 'ready_for_pickup') {
        $order->status = 'awaiting_payment';
        $order->save();
    }

    return back()->with('success', 'Đơn hàng đã được thanh toán, đang đi giao.');
}

public function moveToOutForDelivery(string $id)
{
    $order = Order::findOrFail($id);

    // Chuyển trạng thái từ "Đã thanh toán → Đi giao hàng" -> "Đã giao hàng → Chờ khách xác nhận"
    if ($order->status === 'awaiting_payment') {
        $order->status = 'out_for_delivery';
        $order->save();
    }

    return back()->with('success', 'Đơn hàng đã được giao, chờ khách xác nhận.');
}

public function moveToDelivered(string $id)
{
    $order = Order::findOrFail($id);

    // Chuyển trạng thái từ "Đã giao hàng → Chờ khách xác nhận" -> "Khách đã xác nhận → Hoàn thành"
    if ($order->status === 'out_for_delivery') {
        $order->status = 'delivered';
        $order->save();
    }

    return back()->with('success', 'Khách đã xác nhận, đơn hàng đã hoàn thành.');
}

public function completeOrder(string $id)
{
    $order = Order::findOrFail($id);

    // Chuyển trạng thái từ "Khách đã xác nhận → Hoàn thành" -> "Hoàn thành"
    if ($order->status === 'delivered') {
        $order->status = 'completed';
        $order->save();
    }

    return back()->with('success', 'Đơn hàng đã hoàn thành.');
}

public function cancelOrder(string $id)
{
    $order = Order::findOrFail($id);

    // Chuyển trạng thái thành "Đã hủy"
    if ($order->status !== 'cancelled') {
        $order->status = 'cancelled';
        $order->save();
    }

    return back()->with('success', 'Đơn hàng đã bị hủy.');
  }
}
