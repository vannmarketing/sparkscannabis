@php
    $cart = \Botble\Ecommerce\Facades\Cart::instance('cart')->content();
    echo '<pre>';
    foreach ($cart as $cartItem) {
        echo 'Product: ' . $cartItem->name . '<br>';
        echo 'Options: ' . json_encode($cartItem->options, JSON_PRETTY_PRINT) . '<br><br>';
    }
    echo '</pre>';
@endphp
