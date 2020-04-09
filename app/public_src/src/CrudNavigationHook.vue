<template>
    <div>
        <!--
        Crud class listing
        <b-collapse id="crud-classes" accordion="my-accordion" role="tabpanel">
            <b-card-body>

            </b-card-body>
        </b-collapse>
        -->
        <!-- :contentToLoad=loadCrud" -->
        <tree-menu class="small" v-for="(node, index) in crud" v-bind:key="index" :nodes="node" :label="index" :contentToLoad="loadCrud" :depth="1"></tree-menu>
    </div>
</template>

<script>

    import TreeMenu from '@GuzabaPlatform.Platform/components/TreeMenu.vue'

    export default {
        name: "CrudNavigationHook",
        components: {
            TreeMenu
        },
        data() {
            return {
                crud: [],
            }
        },
        // computed: {
        //     crud: function() {
        //
        //     },
        // },
        // mounted() {
        //     this.$root.$on('bv::collapse::state', (collapseId, isJustShown) => {
        //         if (isJustShown == true && collapseId == "crud-classes") {
        //             this.resetData();
        //
        //             var self = this;
        //
        //             this.$http.get('/crud-classes')
        //                 .then(resp => {
        //                     self.crud = resp.data.classes;
        //                 })
        //                 .catch(err => {
        //                     console.log(err);
        //                 })
        //         } else if (isJustShown == true && collapseId == "crud-permissions") {
        //             this.resetData();
        //
        //             var self = this;
        //
        //             this.$http.get('/permissions-controllers')
        //                 .then(resp => {
        //                     self.permissions = resp.data.tree;
        //                 })
        //                 .catch(err => {
        //                     console.log(err);
        //                 })
        //         }
        //     })
        // },
        methods: {
            resetData() {
                //this.classes = [];
                //this.controllers = [];
                //this.nonControllers = [];
                //this.permissions = [];
                this.crud = [];
            },

            loadCrud(className) {
                //console.log('loadcurd ' + className);
                //console.log(this);
                //this.$router.push('/admin/crud/' + className.split('\\').join('-'));
                //this.$router.push('/admin/crud/' + 'asdas\\ffff');
                this.$router.push('/admin/crud/' + className.split('\\').join('-'));
                //this.$router.push('/admin/components');
                //this.$router.push('/asdfasd');
                //this.$emit('loadContent', 'Crud', {selectedClassName: className});

            },

            loadPermissions(methodName) {
                this.$emit('loadContent', 'Permissions', {selectedMethod: methodName});
            }
        },
        created() {
            // this.$http.get('/admin-navigation')
            //     .then(resp => {
            //         console.log(resp.data.links)
            //         this.links = resp.data.links;
            //     })
            //     .catch(err => {
            //         console.log(err);
            //     });
            this.resetData();
            var self = this;
            this.$http.get('/admin/crud-classes')
                .then(resp => {
                    self.crud = resp.data.classes;
                })
                .catch(err => {
                    console.log(err);
                })
        }
    }
</script>

<style scoped>

</style>