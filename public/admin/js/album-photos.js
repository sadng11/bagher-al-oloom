(function () {
  if (typeof window.Nova === 'undefined' || typeof window.Vue === 'undefined') {
    return;
  }

  const { h, resolveComponent } = window.Vue;

  const STYLE_ID = 'album-photos-nova-styles';
  function ensureStyles() {
    if (document.getElementById(STYLE_ID)) return;
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
      .ap-list { display: flex; flex-direction: column; gap: 12px; width: 100%; }
      .ap-card {
        display: flex;
        flex-wrap: nowrap;
        align-items: flex-start;
        gap: 14px;
        padding: 12px 14px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 10px;
        background: rgba(248, 250, 252, 0.7);
      }
      .dark .ap-card {
        background: rgba(30, 41, 59, 0.55);
        border-color: rgba(148, 163, 184, 0.2);
      }
      .ap-thumb-box {
        width: 96px;
        min-width: 96px;
        max-width: 96px;
        height: 76px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.35);
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }
      .dark .ap-thumb-box { background: #0f172a; }
      .ap-thumb-img {
        width: 96px !important;
        height: 76px !important;
        max-width: 96px !important;
        max-height: 76px !important;
        object-fit: cover;
        display: block;
      }
      .ap-thumb-empty {
        font-size: 11px;
        color: #94a3b8;
        text-align: center;
        padding: 4px;
      }
      .ap-body {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
      }
      .ap-topbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
      }
      .ap-file-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
      }
      .ap-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        background: rgba(14, 165, 233, 0.12);
        color: #0284c7;
      }
      .ap-upload-btn {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        background: #0ea5e9;
        color: #ffffff;
        transition: background-color 0.15s ease;
      }
      .ap-upload-btn:hover { background: #0284c7; }
      .ap-file-meta {
        font-size: 12px;
        color: #64748b;
        max-width: 260px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .ap-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
      }
      .ap-btn-sm {
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 6px;
        border: 1px solid rgba(148, 163, 184, 0.4);
        background: #ffffff;
        color: #334155;
        cursor: pointer;
      }
      .dark .ap-btn-sm {
        background: #1e293b;
        color: #e2e8f0;
      }
      .ap-btn-sm:disabled { opacity: 0.4; cursor: not-allowed; }
      .ap-btn-danger {
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 6px;
        border: none;
        background: #ef4444;
        color: #ffffff;
        cursor: pointer;
      }
      .ap-btn-danger:hover { background: #dc2626; }
      .ap-caption-input {
        width: 100%;
        min-height: 58px;
        padding: 8px 10px;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.45);
        background: #ffffff;
        color: inherit;
        font-size: 13px;
        line-height: 1.6;
        resize: vertical;
      }
      .dark .ap-caption-input { background: #0f172a; }
      .ap-caption-input:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.2);
      }
      .ap-add-btn {
        align-self: flex-start;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 8px;
        border: none;
        background: #0ea5e9;
        color: #ffffff;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
      }
      .ap-add-btn:hover { background: #0284c7; }
      .ap-error { font-size: 12px; color: #ef4444; margin-top: 2px; }
      .ap-detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: 14px;
        width: 100%;
      }
      .ap-detail-card {
        border: 1px solid rgba(148, 163, 184, 0.3);
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
      }
      .dark .ap-detail-card { background: #0f172a; }
      .ap-detail-img {
        width: 100%;
        height: 135px;
        object-fit: cover;
        display: block;
      }
      .ap-detail-body { padding: 8px 10px; font-size: 12px; line-height: 1.6; }
      @media (max-width: 640px) {
        .ap-card { flex-direction: column; }
        .ap-thumb-box { width: 100%; max-width: 100%; height: 140px; }
        .ap-thumb-img { width: 100% !important; max-width: 100% !important; height: 140px !important; max-height: 140px !important; }
      }
    `;
    document.head.appendChild(style);
  }

  let uidCounter = 1;
  function nextKey() {
    uidCounter += 1;
    return 'ap-' + uidCounter + '-' + Date.now();
  }

  function createEmptyItem() {
    return {
      localKey: nextKey(),
      id: null,
      image_url: null,
      thumbnail_url: null,
      preview_url: null,
      file: null,
      file_name: '',
      caption: '',
      client_error: '',
      dimensions: null,
      file_size_kb: null,
    };
  }

  const FormAlbumPhotos = {
    props: ['resourceName', 'resourceId', 'field', 'errors', 'showHelpText', 'mode'],
    data() {
      return {
        items: [],
        deletedIds: [],
      };
    },
    mounted() {
      ensureStyles();
      const existing = Array.isArray(this.field.value) ? this.field.value : [];
      this.items = existing.map((item) => ({
        localKey: nextKey(),
        id: item.id || null,
        image_url: item.image_url || null,
        thumbnail_url: item.thumbnail_url || item.image_url || null,
        preview_url: null,
        file: null,
        file_name: '',
        caption: item.caption || '',
        client_error: '',
        dimensions: item.dimensions || null,
        file_size_kb: item.file_size_kb || null,
      }));

      if (this.items.length === 0) {
        this.items.push(createEmptyItem());
      }

      this.field.fill = (formData) => {
        let submitIndex = 0;

        this.items.forEach((item) => {
          const hasCaption = Boolean(item.caption && item.caption.trim() !== '');
          if (!item.id && !item.file && !hasCaption) {
            return;
          }

          if (item.id) {
            formData.append(`album_photos[${submitIndex}][id]`, String(item.id));
          }
          formData.append(`album_photos[${submitIndex}][caption]`, item.caption || '');
          formData.append(`album_photos[${submitIndex}][sort_order]`, String(submitIndex));

          if (item.file) {
            formData.append(`album_photos[${submitIndex}][image]`, item.file, item.file.name);
          }

          submitIndex += 1;
        });

        this.deletedIds.forEach((id, idx) => {
          formData.append(`deleted_photo_ids[${idx}]`, String(id));
        });
      };
    },
    beforeUnmount() {
      this.items.forEach((item) => {
        if (item.preview_url) {
          URL.revokeObjectURL(item.preview_url);
        }
      });
    },
    methods: {
      addRow() {
        this.items.push(createEmptyItem());
      },
      removeRow(index) {
        const item = this.items[index];
        if (!item) return;

        if (item.preview_url) {
          URL.revokeObjectURL(item.preview_url);
        }
        if (item.id && !this.deletedIds.includes(item.id)) {
          this.deletedIds.push(item.id);
        }
        this.items.splice(index, 1);

        if (this.items.length === 0) {
          this.items.push(createEmptyItem());
        }
      },
      moveRow(index, delta) {
        const target = index + delta;
        if (target < 0 || target >= this.items.length) return;
        const copy = this.items.slice();
        const moved = copy.splice(index, 1)[0];
        copy.splice(target, 0, moved);
        this.items = copy;
      },
      onFileChange(index, event) {
        const input = event.target;
        const file = input.files && input.files[0] ? input.files[0] : null;
        const item = this.items[index];
        if (!item) return;

        item.client_error = '';

        if (!file) {
          return;
        }

        const maxBytes = (this.field.maxUploadKb || 2048) * 1024;
        if (file.size > maxBytes) {
          item.client_error = 'حجم فایل انتخاب‌شده نباید بیشتر از ۲ مگابایت باشد.';
          input.value = '';
          return;
        }

        const allowed = Array.isArray(this.field.acceptedExtensions)
          ? this.field.acceptedExtensions
          : ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif'];
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        if (ext && !allowed.includes(ext)) {
          item.client_error = 'فرمت فایل مجاز نیست. فرمت‌های مجاز: ' + allowed.join(', ');
          input.value = '';
          return;
        }

        if (item.preview_url) {
          URL.revokeObjectURL(item.preview_url);
        }

        item.file = file;
        item.file_name = file.name;
        item.preview_url = URL.createObjectURL(file);
      },
      serverErrorsForRow(index) {
        const errorMap = this.errors && this.errors.errors ? this.errors.errors : {};
        const prefix = `album_photos.${index}.`;
        const messages = [];
        Object.keys(errorMap).forEach((key) => {
          if (key.startsWith(prefix) && Array.isArray(errorMap[key])) {
            messages.push(...errorMap[key]);
          }
        });
        return messages;
      },
    },
    render() {
      const DefaultField = resolveComponent('DefaultField');
      const acceptedAttr = '.' + (this.field.acceptedExtensions || ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif']).join(',.');

      const rows = this.items.map((item, index) => {
        const thumbSrc = item.preview_url || item.thumbnail_url;
        const rowErrors = this.serverErrorsForRow(index);
        if (item.client_error) {
          rowErrors.unshift(item.client_error);
        }

        return h('div', { key: item.localKey, class: 'ap-card' }, [
          h(
            'div',
            { class: 'ap-thumb-box' },
            thumbSrc
              ? [h('img', { src: thumbSrc, alt: item.caption || 'تصویر آلبوم', class: 'ap-thumb-img' })]
              : [h('span', { class: 'ap-thumb-empty' }, 'بدون تصویر')]
          ),
          h('div', { class: 'ap-body' }, [
            h('div', { class: 'ap-topbar' }, [
              h('div', { class: 'ap-file-row' }, [
                h('span', { class: 'ap-badge' }, `تصویر ${index + 1}`),
                h('label', { class: 'ap-upload-btn' }, [
                  h('span', null, item.id ? 'تغییر عکس' : 'انتخاب عکس'),
                  h('input', {
                    type: 'file',
                    accept: acceptedAttr,
                    style: { display: 'none' },
                    onChange: (e) => this.onFileChange(index, e),
                  }),
                ]),
                h(
                  'span',
                  { class: 'ap-file-meta', title: item.file_name || '' },
                  item.file_name
                    ? item.file_name
                    : item.id
                      ? (item.dimensions ? `${item.dimensions}` : 'ذخیره‌شده')
                      : 'حداکثر ۲ مگابایت'
                ),
              ]),
              h('div', { class: 'ap-actions' }, [
                h(
                  'button',
                  {
                    type: 'button',
                    disabled: index === 0,
                    onClick: () => this.moveRow(index, -1),
                    class: 'ap-btn-sm',
                    title: 'انتقال به بالا',
                  },
                  '▲'
                ),
                h(
                  'button',
                  {
                    type: 'button',
                    disabled: index === this.items.length - 1,
                    onClick: () => this.moveRow(index, 1),
                    class: 'ap-btn-sm',
                    title: 'انتقال به پایین',
                  },
                  '▼'
                ),
                h(
                  'button',
                  {
                    type: 'button',
                    onClick: () => this.removeRow(index),
                    class: 'ap-btn-danger',
                  },
                  'حذف'
                ),
              ]),
            ]),
            h('textarea', {
              rows: 2,
              value: item.caption,
              placeholder: 'توضیح تصویر را بنویسید (اختیاری)...',
              class: 'ap-caption-input',
              onInput: (e) => {
                item.caption = e.target.value;
              },
            }),
            rowErrors.length > 0
              ? h('div', { class: 'ap-error' }, rowErrors.map((msg, mIdx) => h('div', { key: mIdx }, msg)))
              : null,
          ]),
        ]);
      });

      return h(
        DefaultField,
        {
          field: this.field,
          errors: this.errors,
          showHelpText: this.showHelpText,
          fullWidthContent: true,
        },
        {
          field: () =>
            h('div', { class: 'ap-list' }, [
              ...rows,
              h(
                'button',
                {
                  type: 'button',
                  onClick: () => this.addRow(),
                  class: 'ap-add-btn',
                },
                '＋ افزودن تصویر جدید به آلبوم'
              ),
            ]),
        }
      );
    },
  };

  const DetailAlbumPhotos = {
    props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],
    mounted() {
      ensureStyles();
    },
    render() {
      const PanelItem = resolveComponent('PanelItem');
      const items = Array.isArray(this.field.value) ? this.field.value : [];

      return h(
        PanelItem,
        { index: this.index, field: this.field },
        {
          value: () =>
            items.length === 0
              ? h('span', { style: { color: '#94a3b8', fontSize: '13px' } }, 'هنوز تصویری در این آلبوم ثبت نشده است.')
              : h(
                  'div',
                  { class: 'ap-detail-grid' },
                  items.map((item) =>
                    h('div', { key: item.id, class: 'ap-detail-card' }, [
                      h('a', { href: item.image_url, target: '_blank', rel: 'noopener' }, [
                        h('img', {
                          src: item.thumbnail_url || item.image_url,
                          alt: item.caption || 'تصویر',
                          class: 'ap-detail-img',
                        }),
                      ]),
                      h('div', { class: 'ap-detail-body' }, [
                        h('div', null, item.caption || 'بدون توضیح'),
                      ]),
                    ])
                  )
                ),
        }
      );
    },
  };

  const IndexAlbumPhotos = {
    props: ['resourceName', 'field'],
    render() {
      const items = Array.isArray(this.field.value) ? this.field.value : [];
      const first = items[0] || null;

      return h('div', { style: { display: 'flex', alignItems: 'center', gap: '8px' } }, [
        first
          ? h('img', {
              src: first.thumbnail_url || first.image_url,
              alt: '',
              style: {
                width: '40px',
                height: '32px',
                objectFit: 'cover',
                borderRadius: '6px',
                border: '1px solid rgba(148, 163, 184, 0.35)',
              },
            })
          : null,
        h('span', { style: { fontSize: '12px', fontWeight: '600' } }, `${items.length} تصویر`),
      ]);
    },
  };

  window.Nova.booting((app) => {
    app.component('form-album-photos', FormAlbumPhotos);
    app.component('detail-album-photos', DetailAlbumPhotos);
    app.component('index-album-photos', IndexAlbumPhotos);
  });
})();
