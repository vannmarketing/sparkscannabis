@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ trans('plugins/free-gifts::manual-gifts.send_manual_gift') }}</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        {{ trans('plugins/free-gifts::manual-gifts.instructions') }}
                    </div>

                    {!! Form::open(['route' => 'manual-gifts.send', 'method' => 'POST', 'id' => 'manual-gift-form']) !!}
                        <div class="form-group mb-3">
                            <label for="customer_id" class="control-label required">{{ trans('plugins/free-gifts::manual-gifts.select_customer') }}</label>
                            {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select-search-full', 'id' => 'customer_id', 'required' => true]) !!}
                        </div>

                        <div class="form-group mb-3">
                            <label class="control-label required">{{ trans('plugins/free-gifts::manual-gifts.select_products') }}</label>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{{ trans('plugins/free-gifts::manual-gifts.product') }}</th>
                                            <th>{{ trans('plugins/free-gifts::manual-gifts.quantity') }}</th>
                                            <th>{{ trans('plugins/free-gifts::manual-gifts.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="manual-gift-products">
                                        <!-- Products will be added here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <select id="add-product-select" class="form-control select-search-full">
                                        <option value="">{{ trans('plugins/free-gifts::manual-gifts.select_product') }}</option>
                                        @foreach($products as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" id="add-product-btn" class="btn btn-secondary">
                                        {{ trans('plugins/free-gifts::manual-gifts.add_product') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary" id="send-gift-btn">
                                {{ trans('plugins/free-gifts::manual-gifts.send_gift') }}
                            </button>
                        </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer')
    <script>
        $(document).ready(function() {
            $('#add-product-btn').on('click', function() {
                const productId = $('#add-product-select').val();
                if (!productId) {
                    return;
                }

                const productName = $('#add-product-select option:selected').text();
                const rowExists = $(`input[name="product_ids[]"][value="${productId}"]`).length > 0;

                if (rowExists) {
                    Botble.showError('{{ trans('plugins/free-gifts::manual-gifts.product_already_added') }}');
                    return;
                }

                const row = `
                    <tr class="product-row">
                        <td>
                            ${productName}
                            <input type="hidden" name="product_ids[]" value="${productId}">
                        </td>
                        <td>
                            <input type="number" name="quantities[]" class="form-control" value="1" min="1">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger remove-product"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                `;

                $('#manual-gift-products').append(row);
                $('#add-product-select').val('').trigger('change');
            });

            $(document).on('click', '.remove-product', function() {
                $(this).closest('tr').remove();
            });

            $('#manual-gift-form').on('submit', function(e) {
                const productCount = $('input[name="product_ids[]"]').length;
                if (productCount === 0) {
                    e.preventDefault();
                    Botble.showError('{{ trans('plugins/free-gifts::manual-gifts.no_products_selected') }}');
                    return false;
                }
                return true;
            });
        });
    </script>
@endpush
