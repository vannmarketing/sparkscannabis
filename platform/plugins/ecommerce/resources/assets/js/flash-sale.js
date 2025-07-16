$(() => {
    $(document).on('click', '.list-search-data .selectable-item', (event) => {
        event.preventDefault()
        const _self = $(event.currentTarget)
        const $input = _self.closest('.box-search-advance').find('input[type=hidden]')

        const existedValues = []
        $.each($input.val().split(','), (index, el) => {
            if (el && el !== '') {
                existedValues[index] = parseInt(el)
            }
        })

        if ($.inArray(_self.data('id'), existedValues) < 0) {
            if ($input.val()) {
                $input.val(`${$input.val()},${_self.data('id')}`)
            } else {
                $input.val(_self.data('id'))
            }

            const template = $(document).find('#selected_product_list_template').html()
            
            // Check if this is a variation product
            const isVariation = _self.data('is-variation') === 1
            let productName = _self.data('name')
            
            // If it's a variation, add a badge
            if (isVariation) {
                productName += ' <span class="badge bg-info text-white">Variation</span>'
            }

            const productItem = template
                .replace(/__name__/gi, productName)
                .replace(/__id__/gi, _self.data('id'))
                .replace(/__index__/gi, existedValues.length)
                .replace(/__url__/gi, _self.data('url'))
                .replace(/__image__/gi, _self.data('image'))
                .replace(/__price__/gi, _self.data('price'))
            
            _self.closest('.box-search-advance').find('.list-selected-products').show()
            const $addedItem = $(productItem)
            _self.closest('.box-search-advance').find('.list-selected-products').append($addedItem)
            
            // Extract and display variation attributes if available
            if (isVariation) {
                // Get the product name and extract attributes if they're in parentheses
                const fullName = _self.data('name')
                const attributeMatch = fullName.match(/\(([^\)]+)\)/)
                
                if (attributeMatch && attributeMatch[1]) {
                    $addedItem.find('.variation-attributes-display').text(attributeMatch[1])
                }
            }
        }
        _self.closest('.card').hide()
    })

    $(document).on('click', '[data-bb-toggle="product-search-advanced"]', (event) => {
        const _self = $(event.currentTarget)
        const $formBody = _self.closest('.box-search-advance').find('.card')
        $formBody.show()
        $formBody.addClass('active')
        if ($formBody.find('.card-body').length === 0) {
            Botble.showLoading($formBody)

            $.ajax({
                url: _self.data('bb-target'),
                type: 'GET',
                success: (res) => {
                    if (res.error) {
                        Botble.showError(res.message)
                    } else {
                        $formBody.html(res.data)
                    }
                },
                error: (data) => {
                    Botble.handleError(data)
                },
                complete: () => {
                    Botble.hideLoading($formBody)
                },
            })
        }
    })

    $(document).on('keyup', '[data-bb-toggle="product-search-advanced"]', (event) => {
        const _self = $(event.currentTarget)
        const $formBody = _self.closest('.box-search-advance').find('.card')
        setTimeout(() => {
            Botble.hideLoading($formBody)

            $.ajax({
                url: `${_self.data('bb-target')}?keyword=${_self.val()}`,
                type: 'GET',
                success: (res) => {
                    if (res.error) {
                        Botble.showError(res.message)
                    } else {
                        $formBody.html(res.data)
                    }
                },
                error: (data) => {
                    Botble.handleError(data)
                },
                complete: () => {
                    Botble.hideLoading($formBody)
                },
            })
        }, 500)
    })

    $(document).on('click', '.box-search-advance .page-link', (event) => {
        event.preventDefault()
        const $searchBox = $(event.currentTarget)
            .closest('.box-search-advance')
            .find('[data-bb-toggle="product-search-advanced"]')
        if (!$searchBox.closest('.page-item').hasClass('disabled') && $searchBox.data('bb-target')) {
            const $formBody = $searchBox.closest('.box-search-advance').find('.card')
            Botble.showLoading($formBody)

            $.ajax({
                url: `${$(event.currentTarget).prop('href')}&keyword=${$searchBox.val()}`,
                type: 'GET',
                success: (res) => {
                    if (res.error) {
                        Botble.showError(res.message)
                    } else {
                        $formBody.html(res.data)
                    }
                },
                error: (data) => {
                    Botble.handleError(data)
                },
                complete: () => {
                    Botble.hideLoading($formBody)
                },
            })
        }
    })

    $(document).on('click', 'body', (e) => {
        const container = $('.box-search-advance')

        if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.find('.card').hide()
        }
    })

    $(document).on('click', '[data-bb-toggle="product-delete-item"]', (event) => {
        event.preventDefault()
        const $input = $(event.currentTarget).closest('.box-search-advance').find('input[type=hidden]')

        const existedValues = $input.val().split(',')
        $.each(existedValues, (index, el) => {
            el = el.trim()

            if (!_.isEmpty(el)) {
                existedValues[index] = parseInt(el)
            }
        })

        let index = existedValues.indexOf($(event.currentTarget).data('bb-target'))

        if (index > -1) {
            delete existedValues[index]
        }

        $input.val(existedValues.join(','))

        if ($(event.currentTarget).closest('.list-group').find('.list-group-item').length < 2) {
            $(event.currentTarget).closest('.list-selected-products').hide()
        }

        $(event.currentTarget)
            .closest('.list-group')
            .find(`.list-group-item[data-product-id=${$(event.currentTarget).data('bb-target')}]`)
            .remove()
    })
})
