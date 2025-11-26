(function ($) {
    'use strict';

    Dropzone.autoDiscover = false;

    function appendCard(zoneSelector, type, data) {
        const card = document.createElement('div');
        card.className = 'cmp-file-card';
        card.dataset.attachment = data.attachment_id;
        const thumb = document.createElement('div');
        thumb.className = 'cmp-thumb' + (type === 'document' ? ' cmp-doc-thumb' : '');
        thumb.style.backgroundImage = 'url(' + data.preview + ')';
        card.appendChild(thumb);
        if (type === 'document') {
            const title = document.createElement('p');
            title.textContent = data.title;
            card.appendChild(title);
        }
        const btn = document.createElement('button');
        btn.className = 'button cmp-remove-file';
        btn.dataset.id = data.attachment_id;
        btn.dataset.type = type;
        btn.textContent = cmp_admin_ajax.remove_label || 'Eliminar';
        card.appendChild(btn);

        $(zoneSelector)
            .closest('.cmp-metabox-wrapper')
            .find('.cmp-file-list[data-type="' + type + '"] .cmp-file-grid')
            .append(card);
    }

    function initDropzone(selector, type) {
        const $zone = $(selector);
        const $progress = $zone.find('.cmp-progress');
        const $progressBar = $zone.find('.cmp-progress-bar');
        const $progressText = $zone.find('.cmp-progress-text');

        function setProgress(value) {
            $progress.addClass('is-active');
            $progressBar.css('width', value + '%');
            $progressText.text(Math.round(value) + '%');
        }

        function resetProgress() {
            $progress.removeClass('is-active');
            $progressBar.css('width', '0%');
            $progressText.text('0%');
        }

        return new Dropzone(selector, {
            url: cmp_admin_ajax.ajaxurl,
            paramName: 'file',
            maxFilesize: 1024,
            parallelUploads: 5,
            uploadMultiple: false,
            addRemoveLinks: false,
            acceptedFiles: type === 'image' ? 'image/*' : '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar',
            dictFileTooBig: cmp_admin_ajax.too_big || 'El archivo excede el límite de 1GB o el permitido por el servidor.',
            headers: {
                'X-WP-Nonce': cmp_admin_ajax.nonce
            },
            params: function () {
                return {
                    action: 'cmp_upload_file',
                    nonce: cmp_admin_ajax.nonce,
                    post_id: $(selector).data('post'),
                    file_type: type
                };
            },
            init: function () {
                this.on('sending', function () {
                    setProgress(0);
                });
                this.on('totaluploadprogress', function (progress) {
                    setProgress(progress);
                });
                this.on('queuecomplete', function () {
                    setTimeout(resetProgress, 300);
                });
                this.on('error', function (file, message) {
                    const humanMessage = typeof message === 'string' ? message : (message && message.message) ? message.message : 'Error al subir archivo';
                    alert(humanMessage);
                    resetProgress();
                });
                this.on('success', function (file, response) {
                    if (!response || !response.success) {
                        return;
                    }

                    const items = response.data.items || [{
                        attachment_id: response.data.attachment_id,
                        preview: response.data.preview,
                        title: response.data.title,
                        type: response.data.type || type
                    }];

                    items.forEach(function (item) {
                        appendCard(selector, item.type || type, item);
                    });
                });
            }
        });
    }

    function bindRemoveButtons() {
        $(document).on('click', '.cmp-remove-file', function (e) {
            e.preventDefault();
            const button = $(this);
            $.post(cmp_admin_ajax.ajaxurl, {
                action: 'cmp_remove_file',
                nonce: cmp_admin_ajax.nonce,
                attachment_id: button.data('id'),
                file_type: button.data('type'),
                post_id: $('#post_ID').val()
            }, function (response) {
                if (response.success) {
                    button.closest('.cmp-file-card').fadeOut(200, function () {
                        $(this).remove();
                    });
                }
            });
        });
    }

    function bindCopyShortcode() {
        $(document).on('click', '.cmp-copy-shortcode', function (e) {
            e.preventDefault();
            const $row = $(this).closest('.cmp-shortcode-row');
            const $input = $row.find('input');
            $input.trigger('focus').trigger('select');
            const copied = document.execCommand('copy');
            if (copied) {
                $(this).text(cmp_admin_ajax.copied || 'Copiado');
                setTimeout(() => $(this).text('Copiar'), 1500);
            }
        });
    }

    $(document).ready(function () {
        if ($('#cmp-dropzone-images').length) {
            initDropzone('#cmp-dropzone-images', 'image');
            initDropzone('#cmp-dropzone-documents', 'document');
            bindRemoveButtons();
            bindCopyShortcode();
        }
    });
})(jQuery);
