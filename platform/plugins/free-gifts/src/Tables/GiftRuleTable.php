<?php

namespace Botble\FreeGifts\Tables;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\FreeGifts\Models\GiftRule;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Botble\Table\Columns\StatusColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;

class GiftRuleTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(GiftRule::class)
            ->addActions([
                EditAction::make()
                    ->route('gift-rules.edit'),
                DeleteAction::make()
                    ->route('gift-rules.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('gift_type', function (GiftRule $item) {
                return $this->formatGiftType($item->gift_type);
            })
            ->editColumn('criteria_type', function (GiftRule $item) {
                return $this->formatCriteriaType($item->criteria_type);
            })
            ->editColumn('criteria_value', function (GiftRule $item) {
                return $this->formatCriteriaValue($item);
            })
            ->editColumn('active_days', function (GiftRule $item) {
                return $this->formatActiveDays($item->active_days);
            });

        return $this->toJson($data);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $query = $this
            ->getModel()
            ->query()
            ->select([
                'id',
                'name',
                'gift_type',
                'criteria_type',
                'criteria_value',
                'start_date',
                'end_date',
                'active_days',
                'status',
                'created_at',
            ]);

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            NameColumn::make()->route('gift-rules.edit'),
            Column::make('gift_type')
                ->title(trans('plugins/free-gifts::gift-rules.gift_type'))
                ->alignLeft(),
            Column::make('criteria_type')
                ->title(trans('plugins/free-gifts::gift-rules.criteria_type'))
                ->alignLeft(),
            Column::make('criteria_value')
                ->title(trans('plugins/free-gifts::gift-rules.criteria_value'))
                ->alignLeft(),
            Column::make('active_days')
                ->title(trans('plugins/free-gifts::gift-rules.active_days'))
                ->alignLeft(),
            Column::make('start_date')
                ->title(trans('plugins/free-gifts::gift-rules.start_date'))
                ->alignLeft(),
            Column::make('end_date')
                ->title(trans('plugins/free-gifts::gift-rules.end_date'))
                ->alignLeft(),
            StatusColumn::make(),
            CreatedAtColumn::make(),
        ];
    }

    public function buttons(): array
    {
        return $this->addCreateButton(route('gift-rules.create'), 'gift-rules.create');
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('gift-rules.destroy'),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
            'name' => [
                'title' => trans('core/base::tables.name'),
                'type' => 'text',
                'validate' => 'required|max:120',
            ],
            'status' => [
                'title' => trans('core/base::tables.status'),
                'type' => 'select',
                'choices' => BaseStatusEnum::labels(),
                'validate' => 'required|in:' . implode(',', BaseStatusEnum::values()),
            ],
            'created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'type' => 'datePicker',
            ],
        ];
    }

    protected function formatGiftType(string $giftType): string
    {
        $types = [
            'manual' => trans('plugins/free-gifts::gift-rules.gift_types.manual'),
            'automatic' => trans('plugins/free-gifts::gift-rules.gift_types.automatic'),
            'buy_x_get_y' => trans('plugins/free-gifts::gift-rules.gift_types.buy_x_get_y'),
            'coupon_based' => trans('plugins/free-gifts::gift-rules.gift_types.coupon_based'),
        ];

        return $types[$giftType] ?? $giftType;
    }

    protected function formatCriteriaType(string $criteriaType): string
    {
        $types = [
            'cart_subtotal' => trans('plugins/free-gifts::gift-rules.criteria_types.cart_subtotal'),
            'cart_total' => trans('plugins/free-gifts::gift-rules.criteria_types.cart_total'),
            'category_total' => trans('plugins/free-gifts::gift-rules.criteria_types.category_total'),
            'cart_quantity' => trans('plugins/free-gifts::gift-rules.criteria_types.cart_quantity'),
        ];

        return $types[$criteriaType] ?? $criteriaType;
    }

    protected function formatCriteriaValue(GiftRule $item): string
    {
        if ($item->criteria_type === 'cart_quantity') {
            return (string) $item->criteria_value;
        }

        return format_price($item->criteria_value);
    }

    protected function formatActiveDays(?array $activeDays): string
    {
        if (empty($activeDays)) {
            return trans('plugins/free-gifts::gift-rules.all_days');
        }

        $dayLabels = [
            'mon' => trans('plugins/free-gifts::gift-rules.days.monday'),
            'tue' => trans('plugins/free-gifts::gift-rules.days.tuesday'),
            'wed' => trans('plugins/free-gifts::gift-rules.days.wednesday'),
            'thu' => trans('plugins/free-gifts::gift-rules.days.thursday'),
            'fri' => trans('plugins/free-gifts::gift-rules.days.friday'),
            'sat' => trans('plugins/free-gifts::gift-rules.days.saturday'),
            'sun' => trans('plugins/free-gifts::gift-rules.days.sunday'),
        ];

        $days = collect($activeDays)->map(function ($day) use ($dayLabels) {
            return $dayLabels[$day] ?? $day;
        })->implode(', ');

        return $days;
    }
}
