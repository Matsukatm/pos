<span class="">{{ $product->name }}</span>
<br>

<span class="text-xs text-gray-800">
    {{ ns()->currency->define($product->unit_price ?? $product->price) }} each
</span>
<br>
