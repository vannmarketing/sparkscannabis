<?php

namespace Botble\FreeGifts\Services;

use Botble\Ecommerce\Enums\OrderStatusEnum;
use Botble\Ecommerce\Enums\ShippingMethodEnum;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\OrderAddress;
use Botble\Ecommerce\Models\OrderHistory;
use Botble\Ecommerce\Models\OrderProduct;
use Botble\Ecommerce\Models\Product;
use Botble\Payment\Enums\PaymentStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManualGiftService
{
    public function createManualGiftOrder(Customer $customer, array $productIds, array $quantities): Order
    {
        DB::beginTransaction();

        try {
            $order = new Order();
            $order->user_id = 0;
            $order->shipping_amount = 0;
            $order->discount_amount = 0;
            $order->tax_amount = 0;
            $order->sub_total = 0;
            $order->coupon_code = null;
            $order->discount_description = null;
            $order->description = 'Manual gift order';
            $order->amount = 0;
            $order->order_status = OrderStatusEnum::COMPLETED;
            $order->payment_status = PaymentStatusEnum::COMPLETED;
            $order->shipping_method = ShippingMethodEnum::DEFAULT;
            $order->status = OrderStatusEnum::COMPLETED;
            $order->code = get_order_code();
            $order->is_free_shipping = true;
            $order->token = Str::random(29);
            $order->is_confirmed = true;
            $order->customer_id = $customer->id;
            $order->customer_type = Customer::class;
            $order->save();

            // Create order address
            $customerAddress = $customer->addresses()->first();
            if ($customerAddress) {
                $orderAddress = new OrderAddress([
                    'name' => $customerAddress->name ?: $customer->name,
                    'phone' => $customerAddress->phone ?: $customer->phone,
                    'email' => $customer->email,
                    'country' => $customerAddress->country,
                    'state' => $customerAddress->state,
                    'city' => $customerAddress->city,
                    'address' => $customerAddress->address,
                    'zip_code' => $customerAddress->zip_code,
                    'order_id' => $order->id,
                ]);
                $orderAddress->save();
            }

            // Add products to order
            foreach ($productIds as $index => $productId) {
                $product = Product::findOrFail($productId);
                $quantity = $quantities[$index] ?? 1;

                $orderProduct = new OrderProduct([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => $product->image,
                    'qty' => $quantity,
                    'weight' => $product->weight * $quantity,
                    'price' => 0,
                    'tax_amount' => 0,
                    'options' => [
                        'is_free_gift' => true,
                    ],
                    'product_type' => $product->product_type,
                ]);
                $orderProduct->save();
            }

            // Add order history
            OrderHistory::create([
                'action' => 'create_order_from_manual_gift',
                'description' => 'Order was created from manual gift',
                'order_id' => $order->id,
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
