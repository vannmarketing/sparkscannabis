<?php

namespace Botble\FreeGifts\Http\Controllers;

use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\Product;
use Botble\FreeGifts\Http\Requests\ManualGiftRequest;
use Botble\FreeGifts\Models\GiftLog;
use Botble\FreeGifts\Services\ManualGiftService;
use Illuminate\Support\Facades\DB;

class ManualGiftController extends BaseController
{
    public function __construct(protected ManualGiftService $manualGiftService)
    {
    }

    public function index()
    {
        PageTitle::setTitle(trans('plugins/free-gifts::manual-gifts.name'));

        $customers = Customer::query()->pluck('name', 'id')->all();
        $products = Product::query()
            ->where('is_variation', false)
            ->where('status', 'published')
            ->pluck('name', 'id')
            ->all();

        return view('plugins/free-gifts::manual-gifts.index', compact('customers', 'products'));
    }

    public function send(ManualGiftRequest $request, BaseHttpResponse $response)
    {
        try {
            DB::beginTransaction();

            $customerId = $request->input('customer_id');
            $productIds = (array) $request->input('product_ids', []);
            $quantities = (array) $request->input('quantities', []);

            $customer = Customer::query()->findOrFail($customerId);
            $order = $this->manualGiftService->createManualGiftOrder($customer, $productIds, $quantities);

            // Log the gift
            foreach ($productIds as $index => $productId) {
                GiftLog::query()->create([
                    'order_id' => $order->id,
                    'customer_id' => $customerId,
                    'product_id' => $productId,
                    'quantity' => $quantities[$index] ?? 1,
                    'gift_type' => 'manual',
                    'is_manual' => true,
                ]);
            }

            DB::commit();

            return $response
                ->setMessage(trans('plugins/free-gifts::manual-gifts.gift_sent_successfully'));
        } catch (\Exception $exception) {
            DB::rollBack();

            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
