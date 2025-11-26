(function ($) {
    Dropzone.autoDiscover = false;

    const selectors = {
        folderSelect: '#gmp-folder-select',
        shortcode: '#gmp-shortcode-example',
        copyBtn: '#gmp-copy-shortcode',
        imageList: '#gmp-image-list',
        docList: '#gmp-doc-list'
    };

    const fileAcceptImages = '.jpg,.jpeg,.png,.webp,.gif';
    const fileAcceptDocs = '.pdf,.doc,.docx,.xls,.xlsx,.zip,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.webp,.gif';

    function toast(icon, text, title = '') {
        if (typeof Swal === 'undefined') {
            alert(text);
            return;
        }
        Swal.fire({
            icon: icon,
            title: title,
            text: text,
            timer: 2000,
            showConfirmButton: false
        });
    }

    function confirmDialog(text) {
        if (typeof Swal === 'undefined') {
            return Promise.resolve(window.confirm(text));
        }
        return Swal.fire({
            icon: 'question',
            title: text,
            showCancelButton: true,
            confirmButtonText: 'Si',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(result => result.isConfirmed);
    }

    function updateShortcode(folder) {
        const code = `[galeria carpeta="${folder}" vista="grid" por_pagina="20"]`;
        $(selectors.shortcode).text(code);
    }

    function renderFiles(list, target) {
        const $target = $(target).empty();
        list.forEach(item => {
            const thumb = item.thumb || '';
            const bg = thumb ? `style="background-image:url('${thumb}');"` : '';
            const placeholder = thumb ? '' : `<span class="gmp-thumb-placeholder">${(item.ext || '').toUpperCase()}</span>`;
            const html = `<div class="gmp-file">
                <div class="gmp-file-thumb" ${bg}>${placeholder}</div>
                <strong title="${item.name}">${item.name}</strong>
                <div class="gmp-actions"><span>${item.ext}</span><span class="gmp-delete" data-file="${item.path}">Eliminar</span></div>
            </div>`;
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
                toast('success', gmpAdmin.strings.deleted);
            } else {
                toast('error', res.data || 'Error');
            }
        });
    }

    function initDropzones() {
        $('.gmp-dropzone').each(function () {
            const type = $(this).data('type');
            const bar = $(this).siblings('.gmp-progress').find('.gmp-bar');

            const dz = new Dropzone(this, {
                url: `${gmpAdmin.ajax}?action=gmp_upload`,
                method: 'post',
                paramName: 'file',
                maxFilesize: 1024,
                timeout: 0,
                acceptedFiles: type === 'imagenes' ? fileAcceptImages : fileAcceptDocs,
                params: {
                    nonce: gmpAdmin.nonce,
                    file_type: type
                },
                addRemoveLinks: true,
                dictRemoveFile: gmpAdmin.strings.removeFile,
                previewsContainer: this,
                init: function () {
                    this._gmpHadError = false;
                },
                sending: function (file, xhr, formData) {
                    const folder = $(selectors.folderSelect).val();
                    if (!folder) {
                        xhr.abort();
                        this.removeFile(file);
                        toast('warning', gmpAdmin.strings.noFolder);
                        return;
                    }
                    formData.set('nonce', gmpAdmin.nonce);
                    formData.set('file_type', type);
                    formData.append('folder', folder);
                },
                uploadprogress: function (_file, progress) {
                    bar.css('width', progress + '%');
                },
                success: function (file, res) {
                    if (!res || !res.success) {
                        this._gmpHadError = true;
                        const msg = res && res.data ? res.data : gmpAdmin.strings.uploadError;
                        toast('error', msg);
                        this.removeFile(file);
                    }
                },
                error: function (file, message, xhr) {
                    this._gmpHadError = true;
                    let msg = message;
                    if (xhr) {
                        if (xhr.status === 403) {
                            msg = gmpAdmin.strings.server403;
                        } else if (xhr.responseText) {
                            try {
                                const parsed = JSON.parse(xhr.responseText);
                                msg = parsed.data || msg;
                            } catch (e) {
                                msg = xhr.responseText || msg;
                            }
                        }
                    }
                    toast('error', msg);
                    this.removeFile(file);
                },
                queuecomplete: function () {
                    bar.css('width', '0');
                    const folder = $(selectors.folderSelect).val();
                    if (folder) {
                        fetchFiles(folder);
                    }
                    if (!this._gmpHadError && this.getAcceptedFiles().length) {
                        toast('success', gmpAdmin.strings.uploadOk);
                    }
                    this._gmpHadError = false;
                    this.removeAllFiles(true);
                },
                removedfile: function (file) {
                    if (file.previewElement) {
                        file.previewElement.remove();
                    }
                    if (this.getUploadingFiles().length === 0) {
                        bar.css('width', '0');
                    }
                }
            });

            dz.on('addedfile', function () {
                this._gmpHadError = false;
            });
        });
    }

    $(document).on('submit', '#gmp-create-folder', function (e) {
        e.preventDefault();
        const name = $(this).find('input[name="folder_name"]').val();
        if (!name) {
            toast('warning', 'Ingresa un nombre para la carpeta.');
            return;
        }
        $.post(gmpAdmin.ajax, {
            action: 'gmp_create_folder',
            nonce: gmpAdmin.nonce,
            folder_name: name
        }).done(res => {
            if (res.success) {
                $(selectors.folderSelect).append(`<option value="${res.data.folder}">${res.data.folder}</option>`).val(res.data.folder);
                updateShortcode(res.data.folder);
                fetchFiles(res.data.folder);
                toast('success', gmpAdmin.strings.folderCreated || 'Carpeta creada.');
            } else {
                toast('error', res.data || 'Error');
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
        toast('success', 'Shortcode copiado');
    });

    $(document).on('click', '.gmp-delete', function () {
        const folder = $(selectors.folderSelect).val();
        if (!folder) return;
        const el = this;
        confirmDialog(gmpAdmin.strings.deleteConfirm).then(confirmed => {
            if (confirmed) {
                deleteFile(folder, $(el).data('file'), el);
            }
        });
    });

    $(function () {
        initDropzones();
    });
})(jQuery);
