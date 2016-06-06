module.exports = {

    template: '<div class="users-fieldtype"><relate-fieldtype :data.sync="data" :name="name" :config="config" v-if="config.type"></relate-fieldtype></div>',

    props: {
        name: String,
        data: {
            default: function() {
                return [];
            }
        },
        config: {
            type: Object,
            default: function() { return {}; }
        }
    },

    ready: function() {
        Vue.set(this.config, 'type', 'users');
    }

};
