<?php
use App\Models\Order;
use App\Classes\Hook;
use Illuminate\Support\Facades\View;

$prefered_price = $order->settings?->where('key', 'ns_pos_prefered_price')->first()?->value;
$pos_vat = $order->settings?->where('key', 'ns_pos_vat')->first()?->value;
$totalProducts = count($order->combinedProducts);
?>

<div class="w-full h-full font-sans text-lg">
    <div class="w-full md:w-1/2 lg:w-1/3 bg-white p-3 mx-auto">
        {{-- Header / Logo --}}
        <div class="flex items-center justify-center mb-3 border-b-2 border-black pb-2">
            @if(empty(ns()->option->get('ns_invoice_receipt_logo')))
                <h2 class="text-4xl font-medium">{{ ns()->option->get('ns_store_name') }}</h2>
            @else
                <img src="{{ ns()->option->get('ns_invoice_receipt_logo') }}" alt="{{ ns()->option->get('ns_store_name') }}">
            @endif
        </div>

        {{-- Order Info Columns --}}
        <div class="p-3 border-b-2 border-black mb-3 text-base">
            <div class="flex flex-wrap -mx-2">
                <div class="px-2 w-1/2">
                    {!! nl2br($ordersService->orderTemplateMapping('ns_invoice_receipt_column_a', $order)) !!}
                </div>
                <div class="px-2 w-1/2">
                    {!! nl2br($ordersService->orderTemplateMapping('ns_invoice_receipt_column_b', $order)) !!}
                </div>
            </div>
        </div>

        {{-- Products Table --}}
        <table class="w-full text-lg border-collapse">
            <tbody>
                <tr class="border-b-2 border-black">
                    <td colspan="2" class="p-2 font-semibold">{{ __('Product') }}</td>
                    <td class="p-2 font-semibold text-right">{{ __('Total') }}</td>
                </tr>

                @foreach(Hook::filter('ns-receipt-products', $order->combinedProducts) as $product)
                    <tr class="border-b border-black">
                        <td colspan="2" class="p-2">
                            <div class="flex justify-between items-center">
                                <div class="truncate pr-1">
                                    <?php $productName = View::make('pages.dashboard.orders.templates._product-name', compact('product')); ?>
                                    <?php echo Hook::filter('ns-receipt-product-name', $productName->render(), $product); ?>
                                </div>
                                <div class="flex-shrink-0">
                                    (x{{ $product->quantity }})
                                </div>
                            </div>
                        </td>
                        <td class="p-2 text-right">{{ ns()->currency->define($product->total_price) }}</td>
                    </tr>
                @endforeach
            </tbody>

            {{-- VAT / Discounts / Shipping / Total --}}
            <tbody>
                @if($pos_vat === 'products_vat')
                    <tr class="border-b-2 border-black">
                        <td colspan="2" class="p-2">{{ $prefered_price==='net_prices' ? __('Product Taxes') : __('Product Taxes (Included)') }}</td>
                        <td class="p-2 text-right">{{ ns()->currency->define($order->products_tax_value) }}</td>
                    </tr>
                @endif

                @if($order->discount > 0)
                    <tr class="border-b-2 border-black">
                        <td colspan="2" class="p-2">{{ __('Discount') }}@if($order->discount_type==='percentage') ({{ $order->discount_percentage }}%)@endif</td>
                        <td class="p-2 text-right">{{ ns()->currency->define($order->discount) }}</td>
                    </tr>
                @endif

                @if($order->total_coupons > 0)
                    <tr class="border-b-2 border-black">
                        <td colspan="2" class="p-2">{{ __('Coupons') }}</td>
                        <td class="p-2 text-right">{{ ns()->currency->define($order->total_coupons) }}</td>
                    </tr>
                @endif

                @if(ns()->option->get('ns_invoice_display_tax_breakdown') === 'yes')
                    @foreach($order->taxes as $tax)
                        <tr class="border-b-2 border-black">
                            <td colspan="2" class="p-2">{{ $tax->tax_name }} — {{ $order->tax_type==='inclusive'? __('Inclusive') : __('Exclusive') }}</td>
                            <td class="p-2 text-right">{{ ns()->currency->define($tax->tax_value) }}</td>
                        </tr>
                    @endforeach
                @elseif($order->tax_value > 0)
                    <tr class="border-b-2 border-black">
                        <td colspan="2" class="p-2">{{ $order->tax_group?->name ?? __('Unassigned Tax Group') }} ({{ $order->tax_type==='inclusive'? __('Inclusive') : '' }})</td>
                        <td class="p-2 text-right">{{ ns()->currency->define($order->tax_value) }}</td>
                    </tr>
                @endif

                @if($order->shipping > 0)
                    <tr class="border-b-2 border-black">
                        <td colspan="2" class="p-2">{{ __('Shipping') }}</td>
                        <td class="p-2 text-right">{{ ns()->currency->define($order->shipping) }}</td>
                    </tr>
                @endif

                {{-- Total --}}
                <tr class="border-t-2 border-b-2 border-black font-semibold">
                    <td colspan="2" class="p-2">{{ __('Total') }} ({{ $totalProducts }})</td>
                    <td class="p-2 text-right">{{ ns()->currency->define($order->total) }}</td>
                </tr>

                @foreach($order->payments as $payment)
                    <tr class="border-b-2 border-black">
                        <td colspan="2" class="p-2">{{ $paymentTypes[$payment['identifier']] ?? __('Unknown Payment') }}</td>
                        <td class="p-2 text-right">{{ ns()->currency->define($payment['value']) }}</td>
                    </tr>
                @endforeach

                <tr class="border-b-2 border-black">
                    <td colspan="2" class="p-2">{{ __('Paid') }}</td>
                    <td class="p-2 text-right">{{ ns()->currency->define($order->tendered) }}</td>
                </tr>

                @if(in_array($order->payment_status,['refunded','partially_refunded']))
                    @foreach($order->refund as $refund)
                        <tr class="border-b-2 border-black">
                            <td colspan="2" class="p-2">{{ __('Refunded') }}</td>
                            <td class="p-2 text-right">{{ ns()->currency->define(-$refund->total) }}</td>
                        </tr>
                    @endforeach
                @endif

                @switch($order->payment_status)
                    @case(Order::PAYMENT_PAID)
                        <tr class="border-b-2 border-black">
                            <td colspan="2" class="p-2">{{ __('Change') }}</td>
                            <td class="p-2 text-right">{{ ns()->currency->define($order->change) }}</td>
                        </tr>
                    @break
                    @case(Order::PAYMENT_PARTIALLY)
                        <tr class="border-b-2 border-black">
                            <td colspan="2" class="p-2">{{ __('Due') }}</td>
                            <td class="p-2 text-right">{{ ns()->currency->define(abs($order->change)) }}</td>
                        </tr>
                    @break
                @endswitch
            </tbody>
        </table>

        {{-- Note and Footer --}}
        @if($order->note_visibility==='visible')
            <div class="pt-4 pb-3 text-center text-base border-t-2 border-black">
                <strong>{{ __('Note: ') }}</strong>{{ $order->note }}
            </div>
        @endif
        <div class="pt-4 pb-3 text-center text-base border-t-2 border-black">
            {{ ns()->option->get('ns_invoice_receipt_footer') }}
        </div>
    </div>
</div>

@includeWhen(request()->query('autoprint')==='true','/pages/dashboard/orders/templates/_autoprint')