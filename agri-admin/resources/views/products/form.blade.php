{{-- FORM SẢN PHẨM – rộng như cũ nhưng hiển thị nhỏ gọn --}}
@extends('layouts.admin')

@section('content')
@php
  $isEdit   = isset($item->_id);

  // variants
  $variants = old('variants', $item->variants ?? []);
  if (!is_array($variants)) $variants = [];
  $variantRows = max(count($variants), 3);

  // images
  $images = old('images', $item->images ?? []);
  if (!is_array($images)) {
      $images = $images ? [$images] : [];
  }
  $imageRows = max(count($images), 1);

  // Danh mục
  $catOptions = [
      'seeds'      => 'Hạt giống',
      'tools'      => 'Dụng cụ',
      'protection' => 'Sản phẩm bảo vệ',
      'organic'    => 'Sản phẩm hữu cơ',
  ];
  $selectedCat = old('category', $item->category ?? '');
@endphp

<div class="fixed inset-0 bg-slate-900/70 flex items-center justify-center px-4">
  {{-- chiều rộng giữ nguyên: max-w-4xl --}}
  <div class="bg-white rounded-2xl w-full max-w-4xl shadow-2xl text-xs">
    {{-- Header --}}
    <div class="flex items-center justify-between px-3 py-2 border-b">
      <div class="font-semibold">
        {{ $isEdit ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm mới' }}
      </div>
      <a href="{{ route('products.index') }}" class="text-base text-slate-400 leading-none">×</a>
    </div>

    {{-- Form --}}
    <form method="POST"
          action="{{ $isEdit ? route('products.update', $item->_id) : route('products.store') }}"
          class="p-3 space-y-2">
      @csrf
      @if($isEdit) @method('PUT') @endif

      {{-- Tên sản phẩm --}}
      <div>
        <label class="font-semibold">Tên sản phẩm *</label>
        <input name="name"
               value="{{ old('name', $item->name ?? '') }}"
               class="w-full px-2 py-1.5 border rounded text-xs"
               required>
      </div>

      {{-- Danh mục + Trạng thái --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <div>
          <label class="font-semibold">Danh mục *</label>
          <select name="category"
                  class="w-full px-2 py-1.5 border rounded text-xs"
                  required>
            @foreach($catOptions as $value => $label)
              <option value="{{ $value }}" {{ $selectedCat === $value ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="font-semibold">Trạng thái</label>
          @php $status = old('status', $item->status ?? 'active'); @endphp
          <select name="status"
                  class="w-full px-2 py-1.5 border rounded text-xs">
            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Đang bán</option>
            <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Ẩn</option>
          </select>
        </div>
      </div>

      {{-- Thương hiệu + Giá cơ bản --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <div>
          <label class="font-semibold">Thương hiệu</label>
          <input name="brand"
                 value="{{ old('brand', $item->brand ?? '') }}"
                 class="w-full px-2 py-1.5 border rounded text-xs">
        </div>

        <div>
          <label class="font-semibold">Giá cơ bản (đ)</label>
          <input type="number" min="0" step="1000"
                 name="price"
                 value="{{ old('price', $item->price ?? '') }}"
                 class="w-full px-2 py-1.5 border rounded text-xs">
        </div>
      </div>

      {{-- Video URL --}}
      <div>
        <label class="font-semibold">Video URL (YouTube / MP4)</label>
        <input type="url"
               name="videoUrl"
               value="{{ old('videoUrl', $item->videoUrl ?? '') }}"
               class="w-full px-2 py-1.5 border rounded text-xs">
      </div>

      {{-- Ảnh sản phẩm --}}
      <div>
        <label class="font-semibold">Ảnh sản phẩm (URL)</label>
        <p class="text-[10px] text-slate-400 mb-1">
          Mỗi dòng là một URL ảnh. Ảnh đầu tiên sẽ dùng làm thumbnail.
        </p>
        @for($i = 0; $i < $imageRows; $i++)
          <input name="images[]"
                 value="{{ $images[$i] ?? '' }}"
                 class="w-full px-2 py-1.5 border rounded text-xs mb-1">
        @endfor
      </div>

      {{-- Biến thể + Mô tả: 2 cột --}}
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">
        {{-- Variants --}}
        <div>
          <label class="font-semibold block mb-1">Biến thể (variants)</label>
          <p class="text-[10px] text-slate-400 mb-1">
            Ví dụ: "Gói 25kg", "1L"… Hệ thống sẽ lưu thành mảng
            <code>variants[{ name, price, quantity }]</code>.
          </p>

          <div class="overflow-x-auto border rounded">
            <table class="min-w-full text-[10px]">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-1.5 py-1 text-left w-1/2">Tên biến thể</th>
                  <th class="px-1.5 py-1 text-right w-1/4">Giá (đ)</th>
                  <th class="px-1.5 py-1 text-right w-1/4">Tồn kho</th>
                </tr>
              </thead>
              <tbody>
                @for($i = 0; $i < $variantRows; $i++)
                  @php
                    $v = $variants[$i] ?? ['name' => '', 'price' => '', 'quantity' => ''];
                  @endphp
                  <tr class="border-t">
                    <td class="px-1.5 py-1">
                      <input name="variants[{{ $i }}][name]"
                             value="{{ $v['name'] ?? '' }}"
                             class="w-full px-1.5 py-1 border rounded text-[10px]">
                    </td>
                    <td class="px-1.5 py-1">
                      <input type="number" min="0" step="1000"
                             name="variants[{{ $i }}][price]"
                             value="{{ $v['price'] ?? '' }}"
                             class="w-full px-1.5 py-1 border rounded text-[10px] text-right">
                    </td>
                    <td class="px-1.5 py-1">
                      <input type="number" min="0" step="1"
                             name="variants[{{ $i }}][quantity]"
                             value="{{ $v['quantity'] ?? '' }}"
                             class="w-full px-1.5 py-1 border rounded text-[10px] text-right">
                    </td>
                  </tr>
                @endfor
              </tbody>
            </table>
          </div>
        </div>

        {{-- Mô tả --}}
        <div>
          <label class="font-semibold">Mô tả / Thông tin an toàn</label>
          <textarea name="description" rows="7"
                    class="w-full px-2 py-1.5 border rounded text-xs">{{ old('description', $item->description ?? '') }}</textarea>
        </div>
      </div>

      {{-- Nút --}}
      <div class="flex justify-end gap-2 pt-2 border-t mt-1">
        <a href="{{ route('products.index') }}"
           class="px-3 py-1.5 border rounded text-xs">
          {{ $isEdit ? 'Đóng' : 'Huỷ' }}
        </a>
        <button class="px-3 py-1.5 bg-slate-900 text-white rounded text-xs">
          {{ $isEdit ? 'Lưu thay đổi' : 'Lưu' }}
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
