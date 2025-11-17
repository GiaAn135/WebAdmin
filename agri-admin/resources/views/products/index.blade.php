@extends('layouts.admin')

@section('content')
<div class="mb-2">
  <h1 class="text-xl font-semibold">Sản phẩm</h1>
</div>

{{-- Tìm kiếm + Lọc danh mục --}}
<form method="GET" class="flex items-center justify-between gap-3 mb-4">
  <div class="flex-1">
    <input name="q"
           value="{{ request('q') }}"
           placeholder="Tìm kiếm SKU, tên sản phẩm..."
           class="w-full px-3 py-2 bg-white border rounded-lg text-sm">
  </div>
  <div>
    <select name="category"
            class="px-3 py-2 bg-white border rounded-lg text-sm"
            onchange="this.form.submit()">
      <option value="">Tất cả danh mục</option>
      @foreach($categories as $key => $label)
        <option value="{{ $key }}"
                {{ (request('category') ?? '') === $key ? 'selected' : '' }}>
          {{ $label }}
        </option>
      @endforeach
    </select>
  </div>
  <div class="flex items-center gap-2">
    <a href="{{ route('products.create') }}"
       class="px-3 py-2 bg-emerald-600 text-white rounded text-sm">
      + Thêm sản phẩm
    </a>
  </div>
</form>

<div class="bg-white rounded-xl border">
  <div class="overflow-x-auto">
    <table class="min-w-full text-xs">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="px-3 py-2 text-left">SKU</th>
          <th class="px-3 py-2 text-left">Sản phẩm</th>
          <th class="px-3 py-2 text-left">Danh mục</th>
          <th class="px-3 py-2 text-left">Thương hiệu</th>
          <th class="px-3 py-2 text-right">Giá hiển thị</th>
          <th class="px-3 py-2 text-right">Tồn kho</th>
          <th class="px-3 py-2 text-center">Video</th>
          <th class="px-3 py-2 text-center">Trạng thái</th>
          <th class="px-3 py-2 text-center">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse($items as $item)
	@php
    // Lấy variants thành mảng
    $variants = $item->variants ?? [];
    if ($variants instanceof \Illuminate\Support\Collection) {
        $variants = $variants->toArray();
    }
    if (!is_array($variants)) {
        $variants = [];
    }

    // Tồn kho tổng (dùng cho option "Giá cơ bản")
    $stockTotal = 0;
    foreach ($variants as $v) {
        $stockTotal += (int)($v['quantity'] ?? 0);
    }

    // Giá hiển thị mặc định
    $displayPrice = $item->price ?? ($variants[0]['price'] ?? 0);
    // Số lượng hiển thị mặc định (ban đầu = tồn kho tổng)
    $displayQty   = $stockTotal;


            // Mô tả rút gọn
            $desc = \Illuminate\Support\Str::limit(
                trim((string)($item->description ?? '')),
                999
            );

            // Ảnh thumbnail sản phẩm
            $images = $item->images ?? [];
            if ($images instanceof \Illuminate\Support\Collection) {
                $images = $images->toArray();
            }
            $thumbImg = (is_array($images) && isset($images[0])) ? $images[0] : null;

            // Video & thumbnail YouTube
            $videoUrl = $item->videoUrl ?? null;
            $videoThumbUrl = null;
            if ($videoUrl && preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $videoUrl, $m)) {
                $videoThumbUrl = 'https://img.youtube.com/vi/'.$m[1].'/hqdefault.jpg';
            }

            // Label danh mục (nếu mảng $categories có, thì dùng; không thì trả về key)
            $catLabel = $categories[$item->category ?? ''] ?? ($item->category ?? '');
          @endphp

          <tr class="hover:bg-slate-50 align-top">
            {{-- SKU --}}
            <td class="px-3 py-2 font-mono text-[11px] text-slate-600">
              {{ $item->sku ?? '—' }}
            </td>

            {{-- Sản phẩm + mô tả --}}
            <td class="px-3 py-2">
              <div class="flex gap-3">
                @if($thumbImg)
                  <img src="{{ $thumbImg }}"
                       alt=""
                       class="w-12 h-12 rounded object-cover border">
                @endif
                <div>
                  <div class="font-medium text-[13px]">
                    {{ $item->name }}
                  </div>
                  @if($desc)
                    <div class="text-[11px] text-slate-500 mt-0.5">
                      {{ $desc }}
                    </div>
                  @endif
                  @if(count($variants))
                    <div class="text-[11px] text-slate-400 mt-1">
                      @foreach($variants as $v)
                        <span class="inline-block mr-1 mb-0.5 px-1.5 py-0.5 rounded bg-slate-100">
                          {{ $v['name'] ?? '' }}
                        </span>
                      @endforeach
                    </div>
                  @endif
                </div>
              </div>
            </td>

            {{-- Danh mục --}}
            <td class="px-3 py-2">
              <span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[11px]">
                {{ $catLabel }}
              </span>
            </td>

            {{-- Thương hiệu --}}
            <td class="px-3 py-2">
              {{ $item->brand ?? '—' }}
            </td>

            {{-- Giá hiển thị + chọn biến thể --}}
<td class="px-3 py-2 text-right">
  <div class="font-semibold js-price">
    {{ number_format((float)$displayPrice, 0, ',', '.') }} đ
  </div>

  @if(count($variants))
    <div class="mt-1 text-[11px] text-slate-500">
      <select class="js-variant-select border rounded px-1 py-0.5">
        {{-- Giá cơ bản: dùng tồn kho tổng --}}
        <option value=""
                data-price="{{ (float)($item->price ?? $displayPrice) }}"
                data-quantity="{{ (int)$stockTotal }}">
          Giá cơ bản
        </option>
        @foreach($variants as $idx => $v)
          <option value="{{ $idx }}"
                  data-price="{{ (float)($v['price'] ?? 0) }}"
                  data-quantity="{{ (int)($v['quantity'] ?? 0) }}">
            {{ $v['name'] ?? 'Biến thể '.($idx+1) }}
          </option>
        @endforeach
      </select>
    </div>
  @endif
</td>

            {{-- Tồn kho --}}
            <td class="px-3 py-2 text-right">
  <span class="js-stock">
    {{ number_format((int)$displayQty, 0, ',', '.') }}
  </span>
</td>


            {{-- Video thumbnail --}}
            <td class="px-3 py-2 text-center">
              @if($videoUrl)
                @if($videoThumbUrl)
                  <a href="{{ $videoUrl }}" target="_blank" class="inline-block">
                    <img src="{{ $videoThumbUrl }}"
                         alt="Video"
                         class="w-16 h-9 object-cover rounded border">
                  </a>
                @else
                  <a href="{{ $videoUrl }}" target="_blank"
                     class="inline-flex items-center gap-1 text-[11px] text-blue-600 underline">
                    🎬 Xem video
                  </a>
                @endif
              @else
                <span class="text-[11px] text-slate-400">—</span>
              @endif
            </td>

            {{-- Trạng thái --}}
            <td class="px-3 py-2 text-center">
              @if(($item->status ?? '') === 'active')
                <span class="px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px]">
                  Đang bán
                </span>
              @else
                <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-500 text-[11px]">
                  Ẩn
                </span>
              @endif
            </td>

            {{-- Actions --}}
            <td class="px-3 py-2">
              <div class="flex gap-2 justify-center">
                <a href="{{ route('products.edit', $item->_id) }}"
                   class="px-2 py-1 bg-slate-100 rounded"
                   title="Sửa">✏️</a>

                <form method="POST"
                      action="{{ route('products.destroy', $item->_id) }}"
                      onsubmit="return confirm('Xác nhận xoá sản phẩm này?')">
                  @csrf
                  @method('DELETE')
                  <button class="px-2 py-1 bg-slate-100 rounded text-rose-600"
                          title="Xoá">🗑</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9"
                class="px-3 py-6 text-center text-slate-400">
              Chưa có sản phẩm
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="p-3 text-right">
    {{ $items->withQueryString()->links() }}
  </div>
</div>

{{-- JS: đổi giá khi chọn biến thể --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.js-variant-select').forEach(function (selectEl) {
    selectEl.addEventListener('change', function () {
      var tr = this.closest('tr');
      if (!tr) return;

      var priceEl = tr.querySelector('.js-price');
      var stockEl = tr.querySelector('.js-stock');

      var opt = this.options[this.selectedIndex];

      // Giá
      var price = parseFloat(opt.dataset.price || '0');
      if (isNaN(price)) price = 0;

      // Số lượng
      var qty = parseInt(opt.dataset.quantity || '0', 10);
      if (isNaN(qty)) qty = 0;

      try {
        if (priceEl) {
          priceEl.textContent =
            new Intl.NumberFormat('vi-VN').format(price) + ' đ';
        }
        if (stockEl) {
          stockEl.textContent =
            new Intl.NumberFormat('vi-VN').format(qty);
        }
      } catch (e) {
        if (priceEl) priceEl.textContent = price + ' đ';
        if (stockEl) stockEl.textContent = qty;
      }
    });
  });
});
</script>

@endsection
