<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;z

class OrderTrackingController extends Controller
{
public function index(Request $request)
    {
        $status = $request->status;
        $orders = Order::query();

        if ($status) {
            $orders->where('status', $status);
        }

        // Lấy tất cả đơn hàng
        $orders = $orders->get();

        $statusOptions = [
            'pending_admin_approval' => ['value' => 'Chờ Admin duyệt', 'label' => 'Chờ Admin'],
            'approved_by_admin' => ['value' => 'Đã duyệt', 'label' => 'Đã duyệt'],
            'preparing' => ['value' => 'Đang chuẩn bị', 'label' => 'Đang chuẩn bị'],
            'waiting_for_vtnn' => ['value' => 'Chờ VTNN lấy hàng', 'label' => 'Chờ VTNN lấy hàng'],
            'awaiting_payment' => ['value' => 'Chờ thanh toán', 'label' => 'Chờ thanh toán'],
            'out_for_delivery' => ['value' => 'Đang giao hàng', 'label' => 'Đang giao hàng'],
            'delivered' => ['value' => 'Đã giao hàng', 'label' => 'Đã giao hàng'],
            'completed' => ['value' => 'Hoàn tất', 'label' => 'Hoàn tất'],
            'cancelled' => ['value' => 'Đã hủy', 'label' => 'Đã hủy'],
        ];

        return view('orders.tracking', compact('orders', 'statusOptions'));
    }
}

    public function trackOrder(Request $request)
{
    $orders = Order::where('status', 'out_for_delivery')
                   ->whereNotNull('latitude')
                   ->whereNotNull('longitude')
                   ->get();

    // Log the orders to check if the data is correct
    \Log::info($orders);  // Check the log file for the data

    return view('tracking', compact('orders'));
}

    public function approveByAdmin($id)
    {
        // Lấy đơn hàng theo ID
        $order = Order::findOrFail($id);

        // Cập nhật trạng thái đơn hàng thành "Chờ VTNN chuẩn bị"
        $order->status = 'pending_vtnn_prep';  // Trạng thái mới
        $order->save();

        // Trả về trang theo dõi đơn hàng
        return redirect()->route('orders.tracking')->with('success', 'Đơn hàng đã được duyệt và chuyển sang trạng thái Chờ VTNN chuẩn bị.');
    }
}
    }

    return back()->with('success', 'Đơn hàng đã được duyệt.');
}

 public function show($id)
    {
        // Lấy đơn hàng theo ID
        $order = Order::findOrFail($id);

        // Trả về view với dữ liệu đơn hàng
        return view('orders.show', compact('order'));
    }
}

public function moveToPreparing(string $id)
{
    $order = Order::findOrFail($id);

    // Chuyển trạng thái từ "Đã duyệt → Chờ VTNN chuẩn bị hàng" -> "VTNN đã soạn hàng xong → Chờ lấy hàng"
    if ($order->status === 'pending_vtnn_prep) {
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


