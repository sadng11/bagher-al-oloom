(function () {
  if (typeof window.Nova === 'undefined' || typeof window.Vue === 'undefined') {
    return;
  }

  const { h, resolveComponent } = window.Vue;

  const STYLE_ID = 'religious-section-group-nova-styles';
  function ensureStyles() {
    if (document.getElementById(STYLE_ID)) return;
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
      .rsg-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        width: 100%;
      }
      @media (min-width: 768px) {
        .rsg-grid {
          grid-template-columns: repeat(4, minmax(0, 1fr));
        }
      }
      .rsg-option {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 10px 12px;
        border: 1px solid rgba(148, 163, 184, 0.45);
        border-radius: 8px;
        background: #ffffff;
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        user-select: none;
      }
      .dark .rsg-option {
        background: rgba(30, 41, 59, 0.7);
        border-color: rgba(148, 163, 184, 0.25);
      }
      .rsg-option:hover {
        border-color: rgb(var(--colors-primary-500, 14 165 233));
      }
      .rsg-option.is-selected {
        border-color: rgb(var(--colors-primary-500, 14 165 233));
        background: rgba(14, 165, 233, 0.08);
        box-shadow: 0 0 0 1px rgb(var(--colors-primary-500, 14 165 233));
      }
      .dark .rsg-option.is-selected {
        background: rgba(14, 165, 233, 0.16);
      }
      .rsg-radio {
        margin-top: 3px;
        accent-color: rgb(var(--colors-primary-500, 14 165 233));
        width: 16px;
        height: 16px;
        flex-shrink: 0;
        cursor: pointer;
      }
      .rsg-text {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
      }
      .rsg-label {
        font-size: 13px;
        font-weight: 700;
        line-height: 1.4;
      }
      .rsg-hint {
        font-size: 11px;
        opacity: 0.72;
        line-height: 1.35;
      }
    `;
    document.head.appendChild(style);
  }

  const FormReligiousSectionGroup = {
    props: [
      'resourceName',
      'resourceId',
      'field',
      'errors',
      'shownViaNewRelationModal',
      'viaResource',
      'viaResourceId',
      'viaRelationship',
      'formUniqueId',
    ],

    data() {
      return {
        value: this.field.value ?? '',
      };
    },

    mounted() {
      ensureStyles();

      this.field.fill = (formData) => {
        if (this.field.visible !== false) {
          formData.append(this.field.attribute, this.value ?? '');
        }
      };
    },

    methods: {
      selectGroup(groupValue) {
        this.value = groupValue;

        if (this.field) {
          window.Nova.$emit(`${this.field.attribute}-change`, groupValue);

          if (this.formUniqueId) {
            window.Nova.$emit(`${this.formUniqueId}-${this.field.attribute}-change`, groupValue);
          }
        }
      },
    },

    render() {
      const DefaultField = resolveComponent('DefaultField');
      const options = Array.isArray(this.field.options) ? this.field.options : [];
      const radioName = `rsg-${this.field.attribute}-${this.formUniqueId || 'main'}`;

      return h(
        DefaultField,
        {
          field: this.field,
          errors: this.errors,
          showHelpText: true,
          fullWidthContent: true,
        },
        {
          field: () =>
            h(
              'div',
              { class: 'rsg-grid', role: 'radiogroup', 'aria-label': this.field.name },
              options.map((option) => {
                const isSelected = this.value === option.value;

                return h(
                  'label',
                  {
                    key: option.value,
                    class: ['rsg-option', isSelected ? 'is-selected' : ''],
                  },
                  [
                    h('input', {
                      type: 'radio',
                      name: radioName,
                      value: option.value,
                      checked: isSelected,
                      class: 'rsg-radio',
                      onChange: () => this.selectGroup(option.value),
                    }),
                    h('span', { class: 'rsg-text' }, [
                      h('span', { class: 'rsg-label' }, option.label),
                      option.hint ? h('span', { class: 'rsg-hint' }, option.hint) : null,
                    ]),
                  ]
                );
              })
            ),
        }
      );
    },
  };

  window.Nova.booting((app) => {
    app.component('form-religious-section-group', FormReligiousSectionGroup);
  });
})();
