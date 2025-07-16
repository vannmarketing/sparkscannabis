<?php

namespace Botble\FreeGifts\Listeners;

use Botble\Ecommerce\Events\OrderPlacedEvent;
use Botble\FreeGifts\Models\GiftLog;
use Illuminate\Support\Facades\Auth;

class OrderPlacedListener
{
    public function handle(OrderPlacedEvent $event): void
    {
        $order = $event->order;
        $products = $order->products;
        
        foreach ($products as $orderProduct) {
            // Add error handling for JSON decoding
            $options = [];
            if ($orderProduct->options) {
                try {
                    $options = is_array($orderProduct->options) ? $orderProduct->options : json_decode($orderProduct->options, true);
                    if (!is_array($options)) {
                        $options = [];
                    }
                } catch (\Exception $e) {
                    report($e);
                    $options = [];
                }
            }
            
            if (isset($options['is_free_gift']) && $options['is_free_gift']) {
                GiftLog::create([
                    'gift_rule_id' => $options['gift_rule_id'] ?? null,
                    'order_id' => $order->id,
                    'customer_id' => $order->user_id,
                    'product_id' => $orderProduct->product_id,
                    'quantity' => $orderProduct->qty,
                    'gift_type' => $options['gift_rule_id'] ? 'rule' : 'manual',
                    'is_manual' => empty($options['gift_rule_id']),
                ]);
            }
        }
    }
}
