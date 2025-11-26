(function ($) {
    function initLightbox() {
        if (typeof Fancybox !== 'undefined') {
            Fancybox.bind('[data-fancybox]', {
                mainClass: 'gmp-fancybox',
                padding: 0,
                trapFocus: false,
                on: {
                    reveal: () => {
                        if (document.activeElement) {
                            document.activeElement.blur();
                        }
                    }
                }
            });
            $(document).on('click', '.gmp-thumb-view', function () {
                this.blur();
            });
        }
    }

    function initSwiper($gallery) {
        const perView = $gallery.data('per');
        new Swiper($gallery.find('.gmp-swiper')[0], {
            slidesPerView: 1,
            spaceBetween: 12,
            loop: false,
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
            breakpoints: {
                640: { slidesPerView: Math.min(2, perView) },
                1024: { slidesPerView: Math.min(3, perView) }
            }
        });
    }

    function appendItems($gallery, html, view) {
        if (view === 'slider') {
            const swiper = $gallery.find('.gmp-swiper')[0].swiper;
            const temp = $('<div>').html(html);
            temp.find('.swiper-slide').each(function () {
                swiper.appendSlide(this.outerHTML);
            });
            swiper.update();
        } else {
            $gallery.find('.gmp-grid-wrap').append(html);
        }
        initLightbox();
    }

    function bindLoadMore() {
        $(document).on('click', '.gmp-load-more', function () {
            const $btn = $(this);
            const $gallery = $btn.closest('.gmp-gallery');
            const page = parseInt($btn.data('page'), 10) + 1;
            const per = $gallery.data('per');
            const folder = $gallery.data('folder');
            const view = $gallery.data('view');
            $btn.prop('disabled', true).text('Cargando...');
            $.get(gmpFront.ajax, {
                action: 'gmp_frontend_page',
                nonce: gmpFront.nonce,
                folder,
                page,
                per,
                view
            }).done(res => {
                if (res.success) {
                    appendItems($gallery, res.data.html, view);
                    $btn.data('page', page);
                    if (!res.data.has_more) {
                        $btn.remove();
                    } else {
                        $btn.prop('disabled', false).text('Cargar más');
                    }
                }
            });
        });
    }

    $(function () {
        $('.gmp-gallery').each(function () {
            const $gallery = $(this);
            if ($gallery.data('view') === 'slider') {
                initSwiper($gallery);
            }
        });
        initLightbox();
        bindLoadMore();
    });
})(jQuery);
