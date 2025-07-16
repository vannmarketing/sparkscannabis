(function ($) {
    'use strict';

    window.MixAndMatch = {
        init: function () {
            console.log('Initializing Mix and Match...');
            this.initToggle();
            this.initPricingTypeToggle();
            this.initProductSearch();
            this.initRemoveProduct();
            console.log('Mix and Match initialized');
        },

        initToggle: function () {
            let $checkbox = $('#is_mix_and_match');
            let $configSection = $('#mix_and_match_configuration');
            
            // Initial state check
            if ($checkbox.is(':checked')) {
                $configSection.show();
            } else {
                $configSection.hide();
            }

            // Change event
            $checkbox.on('change', function () {
                if ($(this).is(':checked')) {
                    $configSection.show();
                } else {
                    $configSection.hide();
                }
            });
        },

        initPricingTypeToggle: function () {
            let $pricingTypeRadios = $('input[name="pricing_type"]');
            let $fixedPriceContainer = $('#fixed_price_container');

            // Initial state check
            if ($('#pricing_type_fixed').is(':checked')) {
                $fixedPriceContainer.show();
            } else {
                $fixedPriceContainer.hide();
            }

            $pricingTypeRadios.on('change', function () {
                if ($('#pricing_type_fixed').is(':checked')) {
                    $fixedPriceContainer.show();
                } else {
                    $fixedPriceContainer.hide();
                }
            });
        },

        initProductSearch: function () {
            console.log('Initializing product search...');
            let $searchInput = $('#search_products');
            let $searchButton = $('#search_products_button');
            let $debugDiv = $('#search-debug');
            
            if (!$searchInput.length) {
                console.error('Search input not found');
                return;
            }
            
            if (!$searchButton.length) {
                console.error('Search button not found');
                return;
            }
            
            console.log('Search elements found');
            
            // Handle button click
            $searchButton.on('click', function(e) {
                e.preventDefault();
                let keyword = $searchInput.val();
                
                if (!keyword) {
                    $debugDiv.html('<div class="alert alert-warning">Please enter a search term</div>');
                    return;
                }
                
                $debugDiv.html('<div class="alert alert-info">Searching for: ' + keyword + '</div>');
                
                // Show loading spinner
                $('.searching-spinner').removeClass('d-none');
                
                // Clear previous results
                $('.list-search-data').empty();
                
                let searchUrl = route('mix-and-match.search-products');
                console.log('Search URL:', searchUrl);
                
                $.ajax({
                    url: searchUrl,
                    type: 'GET',
                    data: {
                        keyword: keyword,
                        exclude: $('input[name="id"]').val()
                    },
                    success: function(res) {
                        console.log('Search response:', res);
                        
                        let $resultsContainer = $('.list-search-data');
                        
                        if (res.error) {
                            $debugDiv.html('<div class="alert alert-danger">Error: ' + res.message + '</div>');
                            return;
                        }
                        
                        if (!res.data || res.data.length === 0) {
                            $resultsContainer.html('<div class="alert alert-info">No products found matching your search.</div>');
                            return;
                        }
                        
                        // Build results table
                        let $table = $('<div class="table-responsive"><table class="table table-striped table-hover"><thead><tr><th>Image</th><th>Name</th><th>SKU</th><th>Type</th><th>Price</th><th>Action</th></tr></thead><tbody></tbody></table></div>');
                        $resultsContainer.append($table);
                        
                        let $tableBody = $table.find('tbody');
                        
                        // Add products to table
                        $.each(res.data, function(index, product) {
                            console.log('Processing product:', product);
                            
                            let isAlreadySelected = $('#selected_products tr[data-id="' + product.id + '"]').length > 0;
                            // Create the add button
                            let buttonHtml;
                            if (isAlreadySelected) {
                                buttonHtml = '<button type="button" class="btn btn-secondary btn-sm" disabled>Added</button>';
                            } else {
                                buttonHtml = '<button type="button" class="btn btn-primary btn-sm add-product-btn" ' +
                                    'data-id="' + product.id + '" ' +
                                    'data-name="' + product.name + '" ' +
                                    'data-price-html="' + product.price_html + '" ' +
                                    'data-image="' + product.image + '" ' +
                                    'data-type="' + (product.type || 'simple') + '" ' +
                                    'data-is-variation="' + (product.is_variation ? '1' : '0') + '" ' +
                                    'data-attributes="' + (product.attributes || '') + '">Add</button>';
                            }
                            
                            // Product type badge
                            let typeLabel = product.is_variation 
                                ? '<span class="badge bg-info">Variation</span>' 
                                : '<span class="badge bg-primary">Simple</span>';
                            
                            // Use the formatted display name if available, otherwise use the regular name
                            let displayName = product.display_name || product.name;
                            
                            // Just use the product name directly - attributes are already included
                            let nameHtml = product.name;
                            
                            // Create the row with all cells
                            let rowHtml = '<tr>' +
                                '<td><img src="' + product.image + '" alt="' + product.name + '" width="40" height="40" class="img-thumbnail"></td>' +
                                '<td>' + nameHtml + '</td>' +
                                '<td>' + (product.sku || '') + '</td>' +
                                '<td>' + typeLabel + '</td>' +
                                '<td>' + product.price_html + '</td>' +
                                '<td>' + buttonHtml + '</td>' +
                            '</tr>';
                            
                            // Add the row to the table body
                            $tableBody.append(rowHtml);
                        });
                        
                        // Handle add button click
                        $tableBody.find('.add-product-btn').on('click', function() {
                            let $button = $(this);
                            let productId = $button.data('id');
                            let productName = $button.data('name');
                            let priceHtml = $button.data('price-html');
                            let productImage = $button.data('image');
                            let productType = $button.data('type');
                            let isVariation = $button.data('is-variation') === 1;
                            let attributes = $button.data('attributes');
                            
                            console.log('Adding product:', { 
                                id: productId, 
                                name: productName, 
                                type: productType, 
                                isVariation: isVariation,
                                attributes: attributes
                            });
                            
                            // Add product to selected products table
                            MixAndMatch.addProductToSelection(productId, productName, priceHtml, productImage, productType, isVariation, attributes);
                            
                            // Disable the button
                            $button.prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary').text('Added');
                        });
                        
                        $debugDiv.html('<div class="alert alert-success">Found ' + res.data.length + ' products</div>');
                    },
                    error: function(xhr, status, error) {
                        console.error('Search error:', { xhr, status, error });
                        $debugDiv.html('<div class="alert alert-danger">Error: ' + error + '</div>');
                        Botble.handleError(xhr);
                    },
                    complete: function() {
                        $('.searching-spinner').addClass('d-none');
                    }
                });
            });
            
            // Handle pressing enter to search
            $searchInput.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $searchButton.click();
                }
            });
            
            console.log('Product search initialized');
        },

        addProductToSelection: function (productId, productName, productPrice, productImage, productType, isVariation, attributes) {
            let $selectedProductsTable = $('#selected_products');
            
            // Remove "no products selected" row if it exists
            $selectedProductsTable.find('.no-products-selected').remove();
            
            // Create type badge
            let typeBadge = isVariation 
                ? '<span class="badge bg-info ms-2">Variation</span>' 
                : '';
                
            // Get attributes from the button data if available
            if (typeof attributes === 'undefined' || attributes === null) {
                const $button = $('.add-product-btn[data-id="' + productId + '"]');
                if ($button.length) {
                    attributes = $button.attr('data-attributes');
                }
            }
            
            console.log('Adding product with attributes:', attributes);
            
            // Just use the product name directly - attributes are already included in the name
            let nameHtml = productName + ' ' + typeBadge;
            
            // Create the row HTML
            let rowHtml = '<tr data-id="' + productId + '" data-type="' + (productType || 'simple') + '" data-is-variation="' + (isVariation ? '1' : '0') + '">' +
                '<td><img src="' + productImage + '" width="50" alt="' + productName + '"></td>' +
                '<td>' + nameHtml + '</td>' +
                '<td>' + productPrice + '</td>' +
                '<td><input type="number" name="mix_and_match_items[' + productId + '][min_qty]" class="form-control" min="0" value="0"></td>' +
                '<td><input type="number" name="mix_and_match_items[' + productId + '][max_qty]" class="form-control" min="1" value="1"></td>' +
                '<td><button type="button" class="btn btn-danger remove-product-btn"><i class="fa fa-trash"></i></button></td>' +
            '</tr>';
            
            // Add the row to the table
            $selectedProductsTable.append(rowHtml);
            
            // Initialize remove button for the new row
            this.initRemoveProduct();
        },

        initRemoveProduct: function () {
            // Use event delegation to handle dynamically added elements
            $(document).off('click', '.remove-product-btn').on('click', '.remove-product-btn', function () {
                let $row = $(this).closest('tr');
                $row.remove();
                
                // If no products are left, add the "no products selected" row
                if ($('#selected_products tr').length === 0) {
                    $('#selected_products').append(`
                        <tr class="no-products-selected">
                            <td colspan="6" class="text-center">No products selected yet. Use the search box above to find and add products.</td>
                        </tr>
                    `);
                }
            });
        }
    };

    $(document).ready(function () {
        MixAndMatch.init();
    });

})(jQuery);
