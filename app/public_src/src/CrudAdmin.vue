<template>
    <div class="crud" style="margin-bottom: 10px">
        <div class="content">
            <div id="data" class="tab">
                <h3 v-if="selectedClassName!=''">Class "{{selectedClassName}}" crud operations <b-button variant="success" @click="showModal('post', newObject)" size="sm">Create New</b-button> </h3>

                <template v-if="!selectedClassName">
                    <p>No class selected!</p>
                </template>

                <template v-else>
                    <b-form @submit="submitSearch">
                        <b-table striped show-empty :items="items" :fields="fields" empty-text="No records found!" @row-clicked="rowClickHandler" no-local-sorting @sort-changed="sortingChanged" head-variant="dark" table-hover>

                            <template slot="top-row" slot-scope="{ fields }">
                                <td v-for="field in fields">
                                    <!-- <template v-if="field.key=='meta_object_uuid'"> -->
                                    <template v-if="field.key=='action'">
                                        <b-button size="sm" variant="outline-primary" type="submit" @click="search()">Search</b-button>
                                    </template>

                                    <template v-else>
                                        <b-form-input v-model="searchValues[field.key]" type="search" :placeholder="field.label"></b-form-input>
                                    </template>
                                </td>
                            </template>

                            <!-- <template v-slot:cell(meta_object_uuid)="row"> -->
                            <template v-slot:cell(action)="row">
                                <b-button size="sm" variant="outline-danger" v-on:click.stop="" @click="showModal('delete', row.item)">Delete</b-button>

                                <b-button size="sm" variant="outline-success" v-on:click.stop="" @click="showPermissions( row.item)">Permissions</b-button>
                            </template>

                        </b-table>
                    </b-form>
                </template>

                <b-pagination v-if="totalItems > limit" size="md" :total-rows="totalItems" v-model="currentPage" :per-page="limit"  align="center"></b-pagination>

                <b-modal
                        id="crud-modal"
                        :title="modalTitle"
                        :header-bg-variant="modalVariant"
                        header-text-variant="light"
                        body-bg-variant="light"
                        body-text-variant="dark"
                        :ok-title="ButtonTitle"
                        :ok-variant="ButtonVariant"
                        centered
                        @ok="proceedAction($event)"
                        :cancel-disabled="actionState"
                        :ok-disabled="loadingState"
                        :ok-only="actionState && !loadingState"
                        size="lg"
                >
                    <template v-if="!actionState">
                        <p>{{actionTitle}}</p>

                        <b-form-group class="form-group" v-for="(value, index) in putValues" v-if="index!='meta_object_uuid'" v-bind:key="index" :label="index" label-align="right" label-cols="3">

                            <template v-if="action=='delete'">
                                <b-form-input :value="value" disabled></b-form-input>
                            </template>

                            <template v-else>
                                <b-form-input v-model="putValues[index]"></b-form-input>
                            </template>

                        </b-form-group>
                    </template>

                    <template v-else>
                        <p v-if="loadingState">
                            {{loadingMessage}}
                            ...
                        </p>
                        <p v-else>
                            <template v-if="requestError == ''">
                                {{successfulMessage}}
                            </template>
                            <template v-else>
                                The operation can not be performed due to an error:<br />
                                {{requestError}}
                            </template>
                        </p>
                    </template>
                </b-modal>


                <b-modal
                        id="crud-permissions"
                        :title="title_permissions"
                        header-bg-variant="success"
                        header-text-variant="light"
                        body-bg-variant="light"
                        body-text-variant="dark"
                        hide-footer
                        size="xl"
                >
                    <b-table
                            striped
                            show-empty
                            :items="items_permissions"
                            :fields="fields_permissions"
                            empty-text="No records found!"
                            head-variant="dark"
                            table-hover
                            :busy.sync="isBusy_permissions"
                    >

                        <!-- permision_uuid is just a value that can not be used here as it is only for the first row/role -->
                        <template v-slot:[setSlotCell(action_name)]="row" v-for="(permission_uuid, action_name) in items_permissions[0].permissions">
                            <b-form-checkbox :value="row.item.permissions[action_name] ? row.item.permissions[action_name] : 0" unchecked-value="" @change="togglePermission(row.item, action_name, row.item.permissions[action_name] ? 1 : 0)" v-model="row.item.permissions[action_name]"></b-form-checkbox>
                        </template>


                        <!--
                        <template v-slot:cell(create_granted)="row">
                            <b-form-checkbox :value="row.item.create_granted" :unchecked-value="0" @change="togglePermission(row.item, 'create')" v-model="row.item.create_granted"></b-form-checkbox>
                        </template>

                        <template v-slot:cell(read_granted)="row">
                            <b-form-checkbox :value="row.item.read_granted" :unchecked-value="0" @change="togglePermission(row.item, 'read')" v-model="row.item.read_granted"></b-form-checkbox>
                        </template>

                        <template v-slot:cell(write_granted)="row">
                            <b-form-checkbox :value="row.item.write_granted" :unchecked-value="0" @change="togglePermission(row.item, 'write')" v-model="row.item.write_granted"></b-form-checkbox>
                        </template>

                        <template v-slot:cell(delete_granted)="row">
                            <b-form-checkbox :value="row.item.delete_granted" :unchecked-value="0" @change="togglePermission(row.item, 'delete')" v-model="row.item.delete_granted"></b-form-checkbox>
                        </template>

                        <template v-slot:cell(grant_permission_granted)="row">
                            <b-form-checkbox :value="row.item.grant_permission_granted" :unchecked-value="0" @change="togglePermission(row.item, 'grant_permission')" v-model="row.item.grant_permission_granted"></b-form-checkbox>
                        </template>

                        <template v-slot:cell(revoke_permission_granted)="row">
                            <b-form-checkbox :value="row.item.revoke_permission_granted" :unchecked-value="0" @change="togglePermission(row.item, 'revoke_permission')" v-model="row.item.revoke_permission_granted"></b-form-checkbox>
                        </template>
                        -->

                    </b-table>
                </b-modal>

            </div>
        </div>
    </div>

</template>

<script>
    import Hook from '@GuzabaPlatform.Platform/components/hooks/Hooks.vue'
    import { stringify } from 'qs'
    export default {
        name: "CrudAdmin",
        components: {
            Hook
        },
        data() {
            return {
                checkbox_test: '0',
                limit: 1000,
                currentPage: 1,
                totalItems: 0,

                selectedClassName: '',
                selectedClassNameShort: '',
                sortBy: 'none',
                sortDesc: false,

                searchValues: {},
                putValues: {},

                requestError: '',

                action: '',
                actionTitle: '',
                modalTitle: '',
                modalVariant: '',
                ButtonTitle: '',
                ButtonVariant: '',

                crudObjectUuid: '',

                actionState: false,
                loadingState: false,

                loadingMessage: '',
                successfulMessage: '',

                items: [],
                fields: [],

                items_permissions: [
                    //must have a default even empty value to avoid the error on template load
                    {
                        permissions: [],
                    }
                ],
                fields_permissions: [],
                fields_permissions_base: [
                    {
                        key: 'role_id',
                        label: 'Role ID',
                        sortable: true
                    },
                    {
                        key: 'role_name',
                        label: 'Role Name',
                        sortable: true
                    },
                ],

                /*
                fields_permissions:[
                    {
                        key: 'role_id',
                        label: 'Role ID',
                        sortable: true
                    },
                    {
                        key: 'role_name',
                        label: 'Role Name',
                        sortable: true
                    },
                    {
                        key: 'create_granted',
                        label: 'Create',
                        sortable: true,
                    },
                    {
                        key: 'read_granted',
                        label: 'Read',
                        sortable: true,
                    },
                    {
                        key: 'write_granted',
                        label: 'Write',
                        sortable: true,
                    },
                    {
                        key: 'delete_granted',
                        label: 'Delete',
                        sortable: true,
                    },
                    {
                        key: 'grant_permission_granted',
                        label: 'Grant Permission',
                        sortable: true,
                    },
                    {
                        key: 'revoke_permission_granted',
                        label: 'Revoke Permission',
                        sortable: true,
                    }
                ],
                */
                title_permissions: "Permissions",
                isBusy_permissions: false,
                selectedObject: {},

                newObject: {}
            }
        },
        methods: {
            // https://stackoverflow.com/questions/58140842/vue-and-bootstrap-vue-dynamically-use-slots
            setSlotCell(action_name) {
                return `cell(${action_name})`;
            },

            submitSearch(evt){
                evt.preventDefault()
                this.search()
            },

            getClassObjects(className) {



                this.fields = [];
                this.newObject = {};

                if (this.selectedClassName != className) {
                    this.resetParams(className);
                    this.searchValues = {};
                }

                className = className.split('\\').join('-');

                for (var key in this.searchValues) {
                    if (this.searchValues[key] == '') {
                        delete this.searchValues[key];
                    }
                }

                let objJsonStr = JSON.stringify(this.searchValues);//this is passed as GET so needs to be stringified
                var searchValuesToPass = encodeURIComponent(window.btoa(objJsonStr));

                var self = this;

                //this.$http.get('/admin/crud-objects/' + this.selectedClassName + '/' + self.currentPage + '/' + self.limit + '/'+ searchValuesToPass + '/' + this.sortBy + '/' + this.sortDesc)
                this.$http.get('/admin/crud-objects/' + className + '/' + self.currentPage + '/' + self.limit + '/'+ searchValuesToPass + '/' + this.sortBy + '/' + this.sortDesc)
                    .then(resp => {
                        // self.fields.push({
                        //     label: 'UUID',
                        //     key: key,
                        //     sortable: true
                        // });
                        for (var i in resp.data.properties) {
                            let key = resp.data.properties[i];
                            self.fields.push({
                                key: key,
                                sortable: true
                            });

                            self.newObject[key] = '';

                            // if (key != 'meta_object_uuid') {
                            //     self.fields.push({
                            //         key: key,
                            //         sortable: true
                            //     });
                            //
                            //     self.newObject[key] = '';
                            // } else {
                            //     self.fields.push({
                            //         key: key,
                            //         label: 'Action',
                            //         sortable: false
                            //     });
                            // }
                        }
                        self.fields.push({
                            label: 'Action',
                            key: 'action',
                            sortable: true
                        });

                        self.items = resp.data.data;

                        self.totalItems = resp.data.totalItems;
                    })
                    .catch(err => {
                        console.log(err);
                    });
            },

            search() {
                this.resetParams(this.selectedClassName);
                this.getClassObjects(this.selectedClassName);
            },

            resetParams(className){
                this.currentPage = 1;
                this.totalItems = 0;
                this.selectedClassName = className;
                this.selectedClassNameShort = className.split("-").pop();
                this.sortBy = 'none';
            },

            rowClickHandler(record, index) {
                this.showModal('put', record);
            },

            showModal(action, row) {
                this.action = action;
                this.crudObjectUuid = null;
                this.putValues = {};

                for (var key in row) {
                    if (key == "meta_object_uuid") {
                        this.crudObjectUuid = row[key];
                    } else if (!key.includes("meta_")){
                        this.putValues[key] = row[key];
                    }
                }

                switch (this.action) {
                    case 'delete' :
                        this.modalTitle = 'Deleting object';
                        this.modalVariant = 'danger';
                        this.ButtonVariant = 'danger';
                        this.actionTitle = 'Are you sure, you want to delete object:';
                        this.ButtonTitle = 'Delete';
                        break;

                    case 'put' :
                        this.modalTitle = 'Edit object';
                        this.modalVariant = 'success';
                        this.ButtonVariant = 'success';
                        //this.actionTitle = this.selectedClassNameShort + ":";
                        this.actionTitle = this.selectedClassName + ":";
                        this.ButtonTitle = 'Save';
                        break;

                    case 'post' :
                        this.modalTitle = 'Create new object';
                        this.modalVariant = 'success';
                        this.ButtonVariant = 'success';
                        //this.actionTitle = this.selectedClassNameShort + ":";
                        this.actionTitle = this.selectedClassName + ":";
                        this.ButtonTitle = 'Save';
                        break;
                }

                if (!this.crudObjectUuid && this.action != "post") {
                    this.requestError = "This object has no meta data!";
                    this.actionState = true
                    this.loadingState = false
                    this.ButtonTitle = 'Ok';
                } else {
                    this.actionState = false
                    this.loadingState = false
                }

                this.$bvModal.show('crud-modal');

            },

            proceedAction(bvEvt) {
                if(!this.actionState) {
                    bvEvt.preventDefault() //if actionState is false, doesn't close the modal
                    this.actionState = true
                    this.loadingState = true

                    let self = this;
                    let sendValues = {};

                    let url = '/admin/crud-operations';

                    switch(this.action) {
                        case 'delete' :
                            self.loadingMessage = 'Deleting object with uuid: ' + this.crudObjectUuid;
                            //url += this.selectedClassName.toLowerCase() + '/' + this.crudObjectUuid;
                            //url += this.selectedClassName.split('\\').join('-') + '/' + this.crudObjectUuid;
                            url += '/' + this.crudObjectUuid;

                            break;

                        case 'put' :
                            self.loadingMessage = 'Saving object with uuid: ' + this.crudObjectUuid;
                            //url += this.selectedClassName.toLowerCase() + '/' + this.crudObjectUuid;
                            //url += this.selectedClassName.split('\\').join('-') + '/' + this.crudObjectUuid;
                            url += '/' + this.crudObjectUuid;

                            sendValues = this.putValues;

                            delete sendValues['meta_object_uuid'];
                            break;

                        case 'post' :
                            self.loadingMessage = 'Saving new object';
                            //url += this.selectedClassName.toLowerCase();
                            //url += this.selectedClassName.split('\\').join('-');

                            sendValues = this.putValues;
                            delete sendValues['meta_object_uuid'];
                            break;
                    }
                    sendValues.crud_class_name = this.selectedClassName.split('\\').join('-');

                    this.$http({
                        method: this.action,
                        url: url,
                        //data: this.$stringify(sendValues)
                        data: sendValues
                    })
                        .then(resp => {
                            self.requestError = '';
                            self.successfulMessage = resp.data.message;
                            self.getClassObjects(self.selectedClassName)
                        })
                        .catch(err => {
                            if (err.response.data.message) {
                                self.requestError = err.response.data.message;
                            } else {
                                self.requestError = err;
                            }
                        })
                        .finally(function(){
                            self.loadingState = false
                            self.actionState = true
                            self.ButtonTitle = 'OK';
                            self.ButtonVariant = 'success';
                        });
                }
            },

            showPermissions(row) {


                if (row.meta_object_id) {
                    this.title_permissions = "Permissions for object of class \"" + row.meta_class_name + "\" with id: " + row.meta_object_id + ", object_uuid: " + row.meta_object_uuid;
                } else {
                    this.title_permissions = "Permissions for object of class \"" + row.meta_class_name + "\" with object_uuid: " + row.meta_object_uuid;
                }

                this.selectedObject = row;
                var self = this;
                this.$http.get('/admin/permissions-objects/' + this.selectedClassName.split('\\').join('-') + '/' + row.meta_object_uuid)
                    .then(resp => {
                        self.items_permissions = Object.values(resp.data.items);
                        //self.fields_permissions = self.fields_permissions_base;//reset the columns
                        self.fields_permissions = JSON.parse(JSON.stringify(self.fields_permissions_base)) //deep clone and produce again Array
                        for (let action_name in self.items_permissions[0].permissions) {
                            self.fields_permissions.push({
                                key: action_name,
                                //label: action_name,
                                label: this.$options.filters.humanize(action_name),
                                sortable: true,
                            });
                        }
                    })
                    .catch(err => {
                        console.log(err);
                        self.requestError = err;
                        self.items_permissions = [];
                    }).finally(function() {
                        self.$bvModal.show('crud-permissions');
                    });

            },

            togglePermission(row, action, checked){
                this.isBusy_permission = true;

                let SendValues = {}

                if (checked) {
                //if (typeof row.permissions[action] != "undefined") {
                    //var object_uuid = row[action + '_granted'];
                    let object_uuid = row.permissions[action];

                    this.action = "delete";

                    var url = 'acl-permission/' + object_uuid;
                } else {
                    this.action = "post";

                    var url = 'acl-permission';

                    SendValues.role_id = row.role_id;
                    if (this.selectedObject.meta_object_id) {
                        SendValues.object_id = this.selectedObject.meta_object_id;
                    } else {
                        SendValues.object_uuid = this.selectedObject.meta_object_uuid;
                    }
                    SendValues.action_name = action;
                    SendValues.class_name = this.selectedClassName.split(".").join("\\");
                }


                var self = this;

                this.$http({
                    method: this.action,
                    url: url,
                    //data: this.$stringify(sendValues)
                    data: SendValues
                })
                    .then(resp => {
                        this.$bvToast.toast(resp.data.message, {
                            // title: '',
                            autoHideDelay: 5000,
                            variant: 'info',
                            appendToast: true,
                            solid: true,
                            noCloseButton: true
                        })
                    })
                    .catch(err => {
                        console.log(err);
                        this.$bvToast.toast(err.response.data.message, {
                            // title: '',
                            autoHideDelay: 5000,
                            variant: 'info',
                            appendToast: true,
                            solid: true,
                            noCloseButton: true
                        })
                        //self.requestError = err;

                    })
                    .finally(function(){
                        self.showPermissions(self.selectedObject)
                        self.isBusy_permission = false;
                    });
            },

            sortingChanged(ctx) {
                this.sortBy = ctx.sortBy;
                this.sortDesc = ctx.sortDesc ? 1 : 0;

                this.getClassObjects(this.selectedClassName);
            }
        },
        props: {
            contentArgs: {}
        },
        watch:{
            contentArgs: {
                handler: function(value) {
                    //this.getClassObjects(value.selectedClassName.split('\\').join('.'));
                }
            },
            currentPage: {
                handler: function(value) {
                    //this.getClassObjects(this.selectedClassName);
                }
            },
            $route (to, from) { // needed because by default no class is loaded and when it is loaded the component for the two routes is the same.
                if (typeof this.$route.params.class !== 'undefined') {
                    this.selectedClassName = this.$route.params.class.split('-').join('\\');
                    //console.log("ASD " + this.selectedClassName)
                    this.getClassObjects(this.selectedClassName);
                }

            }
        },
        mounted() {
            //console.log('wwww');
            //console.log('params: ' + this.$route.params.class)
            //this.getClassObjects(this.contentArgs.selectedClassName.split('\\').join('.'));
            //console.log(this.$route.params);
            if (this.$route.params.class) {
                this.selectedClassName = this.$route.params.class.split('-').join('\\');
                if (this.selectedClassName) {
                    this.getClassObjects(this.selectedClassName);
                }
            }


        },
    };

</script>

<style>
    .content {
        height: 100vh;
        top: 64px;
    }

    .tab {
        float: left;
        height: 100%;
        overflow: none;
        padding: 20px;
    }

    #sidebar{
        font-size: 10pt;
        border-width: 0 5px 0 0;
        border-style: solid;
        width: 30%;
        text-align: left;
    }

    #data {
        width: 100%;
        font-size: 10pt;
    }

    li {
        cursor: pointer;
    }

    /*.btn {*/
    /*    width: 100%;*/
    /*}*/

    tr:hover{
        background-color: #ddd !important;
    }

    th:hover{
        background-color: #000 !important;
    }

    tr {
        cursor: pointer;
    }

</style>
