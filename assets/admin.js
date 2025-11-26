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
                this.on('uploadprogress', function (file, progress) {
                    setProgress(progress);
                });
                this.on('queuecomplete', function () {
                    resetProgress();
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

    $(document).ready(function () {
        if ($('#cmp-dropzone-images').length) {
            initDropzone('#cmp-dropzone-images', 'image');
            initDropzone('#cmp-dropzone-documents', 'document');
            bindRemoveButtons();
        }
    });
})(jQuery);
