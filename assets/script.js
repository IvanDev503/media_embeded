(function ($) {
    'use strict';

    function initLightbox() {
        if (typeof GLightbox !== 'undefined') {
            GLightbox({ selector: '.cmp-grid-item' });
        }
    }

    function initSwiper() {
        $('.cmp-swiper').each(function () {
            if (this.cmpSwiper) {
                this.cmpSwiper.update();
                return;
            }

            this.cmpSwiper = new Swiper(this, {
                slidesPerView: 1,
                spaceBetween: 20,
                loop: true,
                pagination: { el: $(this).find('.swiper-pagination')[0], clickable: true },
                navigation: {
                    nextEl: $(this).find('.swiper-button-next')[0],
                    prevEl: $(this).find('.swiper-button-prev')[0]
                },
                breakpoints: {
                    640: { slidesPerView: 2 },
                    960: { slidesPerView: 3 }
                }
            });
        });
    }

    function renderImage(item, container) {
        const link = $('<a>', {
            class: 'cmp-grid-item',
            href: item.url,
            'data-gallery': container.data('post')
        }).append($('<img>', { src: item.preview, alt: item.title }));
        container.find('.cmp-grid-wrapper, .swiper-wrapper').append(container.hasClass('cmp-slider') ? $('<div>', { class: 'swiper-slide' }).append(link) : link);
    }

    function renderDocument(item, container) {
        const card = $('<div>', { class: 'cmp-doc-card' });
        card.append($('<div>', { class: 'cmp-doc-icon' }).css('background-image', 'url(' + item.preview + ')'));
        const body = $('<div>', { class: 'cmp-doc-body' });
        body.append($('<h4>').text(item.title));
        const actions = $('<div>', { class: 'cmp-doc-actions' });
        const viewer = item.mime.indexOf('pdf') !== -1 ? item.url : 'https://docs.google.com/gview?embedded=1&url=' + encodeURIComponent(item.url);
        actions.append($('<a>', { class: 'cmp-btn', href: viewer, target: '_blank', rel: 'noopener' }).text('Ver'));
        actions.append($('<a>', { class: 'cmp-btn cmp-secondary', href: item.download }).text('Descargar'));
        body.append(actions);
        card.append(body);
        container.append(card);
    }

    function loadMore(button, type) {
        const container = button.closest('.cmp-gallery');
        const offset = parseInt(button.data('offset'), 10);
        const limit = parseInt(container.data('limit'), 10);
        $.get(cmp_ajax.ajaxurl, {
            action: 'cmp_load_media',
            nonce: cmp_ajax.nonce,
            post_id: container.data('post'),
            file_type: type,
            offset: offset,
            limit: limit
        }, function (response) {
            if (response.success) {
                const items = response.data.items || [];
                items.forEach(function (item) {
                    renderImage(item, container);
                });
                if (response.data.remaining > 0) {
                    button.data('offset', offset + limit);
                } else {
                    button.remove();
                }
                initLightbox();
                initSwiper();
            }
        });
    }

    function loadMoreDocs(button) {
        const offset = parseInt(button.data('offset'), 10);
        const limit = parseInt(button.data('limit'), 10);
        const docWrap = button.prevAll('.cmp-documents').first();
        $.get(cmp_ajax.ajaxurl, {
            action: 'cmp_load_media',
            nonce: cmp_ajax.nonce,
            post_id: button.data('post'),
            file_type: 'document',
            offset: offset,
            limit: limit
        }, function (response) {
            if (response.success) {
                const items = response.data.items || [];
                items.forEach(function (item) {
                    renderDocument(item, docWrap);
                });
                if (response.data.remaining > 0) {
                    button.data('offset', offset + limit);
                } else {
                    button.remove();
                }
            }
        });
    }

    $(document).ready(function () {
        initLightbox();
        initSwiper();

        $(document).on('click', '.cmp-load-more', function (e) {
            e.preventDefault();
            loadMore($(this), 'image');
        });

        $(document).on('click', '.cmp-load-more-docs', function (e) {
            e.preventDefault();
            loadMoreDocs($(this));
        });
    });
})(jQuery);
