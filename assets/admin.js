(function ($) {
    'use strict';

    Dropzone.autoDiscover = false;

    function initDropzone(selector, type) {
        return new Dropzone(selector, {
            url: cmp_admin_ajax.ajaxurl,
            paramName: 'file',
            maxFilesize: 20,
            parallelUploads: 5,
            uploadMultiple: false,
            addRemoveLinks: false,
            acceptedFiles: type === 'image' ? 'image/*' : '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar',
            headers: {
                'X-WP-Nonce': cmp_admin_ajax.nonce
            },
            params: function (files, xhr, chunk) {
                return {
                    action: 'cmp_upload_file',
                    nonce: cmp_admin_ajax.nonce,
                    post_id: $(selector).data('post'),
                    file_type: type
                };
            },
            init: function () {
                this.on('success', function (file, response) {
                    if (!response || !response.success) {
                        return;
                    }
                    const card = document.createElement('div');
                    card.className = 'cmp-file-card';
                    card.dataset.attachment = response.data.attachment_id;
                    const thumb = document.createElement('div');
                    thumb.className = 'cmp-thumb' + (type === 'document' ? ' cmp-doc-thumb' : '');
                    thumb.style.backgroundImage = 'url(' + response.data.preview + ')';
                    card.appendChild(thumb);
                    if (type === 'document') {
                        const title = document.createElement('p');
                        title.textContent = response.data.title;
                        card.appendChild(title);
                    }
                    const btn = document.createElement('button');
                    btn.className = 'button cmp-remove-file';
                    btn.dataset.id = response.data.attachment_id;
                    btn.dataset.type = type;
                    btn.textContent = cmp_admin_ajax.remove_label || 'Eliminar';
                    card.appendChild(btn);
                    $(selector).closest('.cmp-metabox-wrapper').find('.cmp-file-list').filter(function () {
                        return $(this).find('h4').text().toLowerCase().indexOf(type === 'image' ? 'imagen' : 'documento') !== -1;
                    }).find('.cmp-file-grid').append(card);
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
