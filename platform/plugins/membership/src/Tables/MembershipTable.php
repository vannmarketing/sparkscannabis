<?php

namespace Botble\Membership\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Membership\Models\Membership;
use Botble\Table\Abstracts\TableAbstract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Botble\Table\DataTables;

class MembershipTable extends TableAbstract
{
    public function __construct(DataTables $table, UrlGenerator $urlGenerator, Membership $membership)
    {
        parent::__construct($table, $urlGenerator);

        $this->model = $membership;

        $this->hasActions = true;
        $this->hasFilter = true;

        if (!Auth::user()->hasAnyPermission(['membership.edit', 'membership.destroy'])) {
            $this->hasOperations = false;
            $this->hasActions = false;
        }
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('name', function (Membership $item) {
                if (!Auth::user()->hasPermission('membership.edit')) {
                    return BaseHelper::clean($item->name);
                }
                return Html::link(route('membership.edit', $item->getKey()), BaseHelper::clean($item->name));
            })
            ->editColumn('checkbox', function (Membership $item) {
                return $this->getCheckbox($item->getKey());
            })
            ->editColumn('created_at', function (Membership $item) {
                return BaseHelper::formatDate($item->created_at);
            })
            ->editColumn('status', function (Membership $item) {
                return $item->status->toHtml();
            })
            ->addColumn('operations', function (Membership $item) {
                return $this->getOperations('membership.edit', 'membership.destroy', $item);
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
               'created_at',
               'status',
           ]);

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            'id' => [
                'title' => trans('core/base::tables.id'),
                'width' => '20px',
            ],
            'name' => [
                'title' => trans('core/base::tables.name'),
                'class' => 'text-start',
            ],
            'created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'width' => '100px',
            ],
            'status' => [
                'title' => trans('core/base::tables.status'),
                'width' => '100px',
            ],
        ];
    }

    public function buttons(): array
    {
        return $this->addCreateButton(route('membership.create'), 'membership.create');
    }

    public function bulkActions(): array
    {
        return $this->addDeleteAction(route('membership.deletes'), 'membership.destroy', parent::bulkActions());
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
                'type' => 'date',
            ],
        ];
    }

    public function getFilters(): array
    {
        return $this->getBulkChanges();
    }
}
