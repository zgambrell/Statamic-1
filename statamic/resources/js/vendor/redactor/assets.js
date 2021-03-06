$.Redactor.prototype.assets = function () {

    return {

        init: function () {
            // If assets aren't enabled, don't add the button.
            if (! this.assets.vue().assetsEnabled) {
                return;
            }

            var button = this.button.add('assets', 'Assets');
            this.button.addCallback(button, this.assets.show);
        },

        show: function () {
            this.assets.vue().addAsset();
        },

        vue: function () {
            return this.$editor.closest('.redactor-wrapper')[0].__vue__;
        }

    }

};
