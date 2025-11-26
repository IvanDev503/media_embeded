(function ($) {
    Dropzone.autoDiscover = false;

    const selectors = {
        folderSelect: '#gmp-folder-select',
        shortcode: '#gmp-shortcode-example',
        copyBtn: '#gmp-copy-shortcode',
        imageList: '#gmp-image-list',
        docList: '#gmp-doc-list',
        bars: '.gmp-progress .gmp-bar'
    };

    function updateShortcode(folder) {
        const code = `[galeria carpeta="${folder}" vista="grid" por_pagina="20"]`;
        $(selectors.shortcode).text(code);
    }

    function renderFiles(list, target) {
        const $target = $(target).empty();
        list.forEach(item => {
            const html = `<div class="gmp-file"><strong>${item.name}</strong><div class="gmp-actions"><span>${item.ext}</span><span class="gmp-delete" data-file="${item.path}">Eliminar</span></div></div>`;
            $target.append(html);
        });
    }

    function fetchFiles(folder) {
        if (!folder) return;
        $.get(gmpAdmin.ajax, {
            action: 'gmp_list_files',
            nonce: gmpAdmin.nonce,
            folder: folder
        }).done(res => {
            if (res.success) {
                renderFiles(res.data.imagenes, selectors.imageList);
                renderFiles(res.data.documentos, selectors.docList);
            }
        });
    }

    function deleteFile(folder, path, el) {
        $.post(gmpAdmin.ajax, {
            action: 'gmp_delete_file',
            nonce: gmpAdmin.nonce,
            folder,
            file: path
        }).done(res => {
            if (res.success) {
                $(el).closest('.gmp-file').remove();
            }
        });
    }

    function initDropzones() {
        $('.gmp-dropzone').each(function () {
            const type = $(this).data('type');
            const bar = $(this).siblings('.gmp-progress').find('.gmp-bar');
            new Dropzone(this, {
                url: `${gmpAdmin.ajax}?action=gmp_upload`,
                paramName: 'file',
                maxFilesize: 1024,
                timeout: 0,
                acceptedFiles: type === 'imagenes' ? '.jpg,.jpeg,.png,.webp,.gif' : '.pdf,.doc,.docx,.xls,.xlsx,.zip,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.webp,.gif',
                params: {
                    action: 'gmp_upload',
                    nonce: gmpAdmin.nonce,
                    file_type: type,
                },
                withCredentials: true,
                sending: function (file, xhr, formData) {
                    const folder = $(selectors.folderSelect).val();
                    if (!folder) {
                        xhr.abort();
                        alert(gmpAdmin.strings.noFolder);
                        return;
                    }
                    formData.append('action', 'gmp_upload');
                    formData.append('folder', folder);
                },
                uploadprogress: function (_file, progress) {
                    bar.css('width', progress + '%');
                },
                queuecomplete: function () {
                    bar.css('width', '0');
                    fetchFiles($(selectors.folderSelect).val());
                },
                success: function (_file, res) {
                    if (!res || !res.success) {
                        const msg = res && res.data ? res.data : 'Error';
                        alert(msg);
                    }
                },
                error: function (_file, message, xhr) {
                    if (xhr && xhr.responseText) {
                        try {
                            const parsed = JSON.parse(xhr.responseText);
                            message = parsed.data || message;
                        } catch (e) {
                            message = xhr.responseText || message;
                        }
                    }
                    alert(message);
                }
            });
        });
    }

    $(document).on('submit', '#gmp-create-folder', function (e) {
        e.preventDefault();
        const name = $(this).find('input[name="folder_name"]').val();
        $.post(gmpAdmin.ajax, {
            action: 'gmp_create_folder',
            nonce: gmpAdmin.nonce,
            folder_name: name
        }).done(res => {
            if (res.success) {
                $(selectors.folderSelect).append(`<option value="${res.data.folder}">${res.data.folder}</option>`).val(res.data.folder);
                updateShortcode(res.data.folder);
                fetchFiles(res.data.folder);
            } else {
                alert(res.data || 'Error');
            }
        });
    });

    $(document).on('change', selectors.folderSelect, function () {
        const folder = $(this).val();
        updateShortcode(folder);
        fetchFiles(folder);
    });

    $(document).on('click', selectors.copyBtn, function (e) {
        e.preventDefault();
        const text = $(selectors.shortcode).text();
        navigator.clipboard.writeText(text);
        $(this).text('Copiado').prop('disabled', true);
        setTimeout(() => $(this).text('Copiar').prop('disabled', false), 1500);
    });

    $(document).on('click', '.gmp-delete', function () {
        const folder = $(selectors.folderSelect).val();
        if (!folder) return;
        if (!confirm(gmpAdmin.strings.deleteConfirm)) return;
        deleteFile(folder, $(this).data('file'), this);
    });

    $(function () {
        initDropzones();
    });
})(jQuery);
