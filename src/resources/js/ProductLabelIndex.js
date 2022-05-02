/** global: Craft */
/** global: Garnish */
// noinspection JSVoidFunctionReturnValueUsed

/**
 * Product Label index class
 */
Craft.ProductLabelIndex = Craft.BaseElementIndex.extend({
    editableTypes: null,
    $newProductLabelBtnGroup: null,
    $newProductLabelBtn: null,

    init: function(elementType, $container, settings) {
        this.editableTypes = [];
        this.on('selectSource', this.updateButton.bind(this));
        this.on('selectSite', this.updateButton.bind(this));
        this.base(elementType, $container, settings);
    },

    afterInit: function() {
        // Find which of the visible groups the user has permission to create new categories in
        this.editableTypes = Craft.productLabelTypes.filter(t => !!this.getSourceByKey(`type:${t.uid}`));

        this.base();
    },

    getDefaultSourceKey: function() {
        // Did they request a specific category group in the URL?
        if (this.settings.context === 'index' && typeof defaultTypeHandle !== 'undefined') {
            for (let i = 0; i < this.$sources.length; i++) {
                const $source = $(this.$sources[i]);
                if ($source.data('handle') === defaultTypeHandle) {
                    return $source.data('key');
                }
            }
        }

        return this.base();
    },

    updateButton: function() {
        if (!this.$source) {
            return;
        }

        // Get the handle of the selected source
        const selectedSourceHandle = this.$source.data('handle');

        // Update the New Category button
        // ---------------------------------------------------------------------

        if (this.editableTypes.length) {
            // Remove the old button, if there is one
            if (this.$newProductLabelBtnGroup) {
                this.$newProductLabelBtnGroup.remove();
            }

            // Determine if they are viewing a group that they have permission to create categories in
            const selectedType = this.editableTypes.find(g => g.handle === selectedSourceHandle);

            this.$newProductLabelBtnGroup = $('<div class="btngroup submit" data-wrapper/>');
            let $menuBtn;
            const menuId = 'new-category-menu-' + Craft.randomString(10);

            // If they are, show a primary "New category" button, and a dropdown of the other groups (if any).
            // Otherwise only show a menu button
            if (selectedType) {
                this.$newProductLabelBtn = Craft.ui.createButton({
                        label: this.settings.context === 'index'
                            ? Craft.t('app', 'New product label')
                            : Craft.t('app', 'New {type} category', {
                                type: selectedType.name,
                            }),
                        spinner: true,
                    })
                    .addClass('submit add icon')
                    .appendTo(this.$newProductLabelBtnGroup);

                this.addListener(this.$newProductLabelBtn, 'click', () => {
                    const uri = selectedType.handle + '/new';
                    window.location.href = uri;
                });

                if (this.editableTypes.length > 1) {
                    $menuBtn = $('<button/>', {
                        type: 'button',
                        class: 'btn submit menubtn btngroup-btn-last',
                        'aria-controls': menuId,
                        'data-disclosure-trigger': '',
                    }).appendTo(this.$newProductLabelBtnGroup);
                }
            } else {
                this.$newProductLabelBtn = $menuBtn = Craft.ui.createButton({
                        label: Craft.t('app', 'New type'),
                        spinner: true,
                    })
                    .addClass('submit add icon menubtn btngroup-btn-last')
                    .attr('aria-controls', menuId)
                    .attr('data-disclosure-trigger', '')
                    .appendTo(this.$newProductLabelBtnGroup);
            }

            this.addButton(this.$newProductLabelBtnGroup);
        }

        // Update the URL if we're on the Product Labels index
        // ---------------------------------------------------------------------

        if (this.settings.context === 'index') {
            let uri = 'productlabels';

            if (selectedSourceHandle) {
                uri += '/' + selectedSourceHandle;
            }

            Craft.setPath(uri);
        }
    },
});

// Register it!
Craft.registerElementIndexClass('thepixelage\\productlabels\\elements\\ProductLabel', Craft.ProductLabelIndex);
