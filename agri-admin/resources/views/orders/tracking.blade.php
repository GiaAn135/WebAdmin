{{-- resources/views/orders/tracking.blade.php --}}

@extends('layouts.admin')

@section('title', 'Theo dõi giao hàng')

@section('content')
<div class="px-6 py-4 space-y-4">

    {{-- Tiêu đề + filter trạng thái --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Theo dõi giao hàng</h1>
            <p class="text-sm text-slate-500 mt-1">
                Bản đồ và danh sách đơn theo trạng thái
            </p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <select name="status"
                    class="px-3 py-2 border rounded-lg text-sm text-slate-700 bg-white">
                <option value="">Tất cả trạng thái</option>
                @foreach($statusOptions as $key => $config)
                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                        {{ $config['value'] }}. {{ $config['label'] }}
                    </option>
                @endforeach
            </select>

            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                Lọc
            </button>
        </form>
    </div>

    {{-- Bản đồ (Đã được thay thế bằng container Google Maps) --}}
    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <div class="px-6 py-3 border-b">
            <h2 class="text-sm font-semibold text-slate-700">Bản đồ giao hàng</h2>
        </div>
        {{-- DIV QUAN TRỌNG: Cần có ID và style height cứng để map hiển thị --}}
        <div id="delivery-map" style="width: 100%; height: 500px;" class="bg-gray-100">
            <div class="h-full flex items-center justify-center text-slate-500 text-sm">
                Đang tải bản đồ...
            </div>
        </div>
    </div>

    {{-- Danh sách đơn (card) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @forelse($orders as $order)
            <div class="bg-white rounded-xl border shadow-sm p-4 flex flex-col justify-between">

                {{-- Header: mã đơn + tổng tiền --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-slate-800">
                            {{ $order->code }}
                        </span>
                        <span class="text-xs font-semibold text-emerald-600">
                            {{ number_format($order->total_amount, 0, ',', '.') }} đ
                        </span>
                    </div>

                    {{-- Badge trạng thái --}}
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium {{ $order->status_badge_class }}">
                        {{ $order->status_label }}
                    </span>
                    {{-- Thêm địa chỉ để hiển thị dễ nhìn hơn trên card --}}
                    @if(isset($order->shipping_address))
                    <p class="text-xs text-slate-500 mt-2 line-clamp-2">
                        <i class="fas fa-map-marker-alt mr-1"></i> {{ $order->shipping_address }}
                    </p>
                    @endif
                </div>

                {{-- Nút action phía dưới --}}
                <div class="mt-4 flex gap-2">
                    {{-- Chuyển qua các trạng thái dựa vào điều kiện --}}
                    @if($order->status === 'pending_admin_approval')
                        <form method="POST" action="{{ route('orders.approveByAdmin', $order->_id ?? $order->id) }}" class="flex-1">
                            @csrf
                            <button type="submit"
                                class="w-full h-9 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                Duyệt
                            </button>
                        </form>
                    @elseif($order->status === 'pending_vtnn_prep')
                        <form method="POST" action="{{ route('orders.moveToPreparing', $order->_id ?? $order->id) }}" class="flex-1">
                            @csrf
                            <button type="submit"
                                class="w-full h-9 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                Chuyển sang chuẩn bị
                            </button>
                        </form>
                    @elseif($order->status === 'preparing')
                        <form method="POST" action="{{ route('orders.moveToReadyForPickup', $order->_id ?? $order->id) }}" class="flex-1">
                            @csrf
                            <button type="submit"
                                class="w-full h-9 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                Sẵn sàng lấy hàng
                            </button>
                        </form>
                    @elseif($order->status === 'ready_for_pickup')
                        <form method="POST" action="{{ route('orders.moveToAwaitingPayment', $order->_id ?? $order->id) }}" class="flex-1">
                            @csrf
                            <button type="submit"
                                class="w-full h-9 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                Chờ thanh toán
                            </button>
                        </form>
                    @elseif($order->status === 'awaiting_payment')
                        <form method="POST" action="{{ route('orders.moveToOutForDelivery', $order->_id ?? $order->id) }}" class="flex-1">
                            @csrf
                            <button type="submit"
                                class="w-full h-9 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                Đi giao hàng
                            </button>
                        </form>
                    @elseif($order->status === 'out_for_delivery')
                        {{-- Chuyển đổi nút khi trạng thái là "Đang giao hàng" --}}
                        <form method="GET" action="{{ route('orders.show', $order->_id ?? $order->id) }}" class="flex-1">
                            <button type="submit"
                                class="w-full h-9 text-xs font-medium rounded-lg bg-white text-slate-800 border border-slate-300 hover:bg-slate-100">
                                Xem chi tiết
                            </button>
                        </form>
                    @elseif($order->status === 'delivered')
                        <form method="POST" action="{{ route('orders.completeOrder', $order->_id ?? $order->id) }}" class="flex-1">
                            @csrf
                            <button type="submit"
                                class="w-full h-9 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                                Hoàn thành
                            </button>
                        </form>
                    @elseif($order->status === 'completed')
                        <form method="POST" action="{{ route('orders.cancelOrder', $order->_id ?? $order->id) }}" class="flex-1">
                            @csrf
                            <button type="submit"
                                class="w-full h-9 text-xs font-medium rounded-lg bg-rose-600 text-white hover:bg-rose-700">
                                Hủy đơn
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500 col-span-full">
                Không có đơn hàng nào phù hợp với bộ lọc.
            </p>
        @endforelse
    </div>
</div>

{{-- KHỐI SCRIPT GOOGLE MAPS --}}
<script>
    function initMap() {
        // Lấy dữ liệu đơn hàng từ PHP
        const orders = @json($orders);
        
        // Tọa độ mặc định (ví dụ: Hà Nội)
        const defaultCenter = { lat: 21.0285, lng: 105.8542 };
        
        const map = new google.maps.Map(document.getElementById("delivery-map"), {
            zoom: 12,
            center: defaultCenter,
        });

        const infoWindow = new google.maps.InfoWindow();
        const bounds = new google.maps.LatLngBounds();
        let hasMarkers = false;

        orders.forEach((order) => {
            // Lấy tọa độ. Bạn cần đảm bảo data $order->latitude và $order->longitude tồn tại
            // Nếu bạn lưu trong các trường khác (ví dụ: lat, lng), hãy sửa lại dòng này.
            let lat = parseFloat(order.latitude || order.lat); 
            let lng = parseFloat(order.longitude || order.lng);

            // Xử lý trường hợp tọa độ nằm trong object address (ví dụ: order.address.lat)
            if (isNaN(lat) && order.shipping_address_data) {
                lat = parseFloat(order.shipping_address_data.lat || order.shipping_address_data.latitude);
                lng = parseFloat(order.shipping_address_data.lng || order.shipping_address_data.longitude);
            }
            
            if (!isNaN(lat) && !isNaN(lng)) {
                const position = { lat: lat, lng: lng };
                
                const marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: order.code,
                    // Tùy chỉnh icon dựa trên trạng thái (ví dụ)
                    icon: order.status === 'out_for_delivery' ? 
                          { url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png' } :
                          { url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png' }
                });

                const content = `
                    <div style="padding:5px; color: black;">
                        <h4 style="margin:0;">Mã Đơn: <b>${order.code}</b></h4>
                        <p style="margin:2px 0; font-size:13px;">Trạng thái: ${order.status_label}</p>
                        <p style="margin:2px 0; font-size:13px;">Tổng tiền: ${new Intl.NumberFormat('vi-VN').format(order.total_amount)} đ</p>
                        <a href="${'/orders/' + (order._id || order.id)}" target="_blank" style="color:#059669; font-size:12px;">Xem chi tiết</a>
                    </div>
                `;

                marker.addListener("click", () => {
                    infoWindow.setContent(content);
                    infoWindow.open(map, marker);
                });

                bounds.extend(position);
                hasMarkers = true;
            }
        });

        // Tự động căn chỉnh bản đồ để bao gồm tất cả marker
        if (hasMarkers) {
            map.fitBounds(bounds);
        }
    }
</script>

{{-- LOAD GOOGLE MAPS API: THAY YOUR_GOOGLE_MAPS_API_KEY BẰNG KEY CỦA BẠN --}}
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCKKh0zOa2fwOfbsMWA1I2J1oviNAuM5h0&callback=initMap" async defer></script>

@endsection