<style>
    #app {
        max-width: 990px;
    }
    .arrow {
        display: inline-block;
        vertical-align: middle;
        width: 0;
        height: 0;
        margin-left: 2px;
        opacity: 0.66;
    }

    .arrow.asc {
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-bottom: 4px solid #000;
    }

    .arrow.dsc {
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 4px solid #000;
    }
    div:empty{
        width: 5em;
        height: 1em;
    }
    .table-condensed th {
        font-size: 10px;
        font-weight: normal;
    }
    .table-condensed th,
    .table-condensed td {
        padding-left: 0;
        padding-right: 0;
    }
    .container-fluid {
        padding-right: 4px;
        padding-left:  4px;
    }
    .table .control-group{
        margin:0
    }
    .table .control-group {
        position: relative;
    }
    .table .control-group .help-inline{
        display: block;
        top: 0;
        position: absolute;
        font-size: 11px;
        opacity: .8;
        width: 96%;
        user-select: none;
        -moz-user-select: none;
    }
    .table .form-control{
        cursor: pointer;
        border: 1px solid transparent;
        box-shadow: none;
        width: 80%;
        min-width: 1rem;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }
    table td{
        vertical-align: middle!important;
    }
    table td .btn{
        color: #212529
    }
    td:nth-child(n+4):nth-child(-n+9){
        text-align: center;
        width: 3rem;
    }
    th:nth-child(n+4):nth-child(-n+9) .d-flex{
        justify-content: center !important;
    }
    .table caption{
        caption-side: bottom
    }
    [v-cloak] { display: none; }

    .caret-dark{
        border-top-color: #212429!important;
    }
    .table-condensed td .form-control[type="text"] {
        padding-top: .4em;
        padding-bottom: .4em;
    }
    .position-absolute{
        position: absolute!important;
    }
    .position-relative {
        position: relative!important;
    }
    .table .form-control .fade{
        opacity: 0!important;
        transition-delay: 2s
    }
    /* small devices */
    @media (min-width: 576px) {
        .btn-sm-md {
            padding: 4px 12px;
            font-size: 14px;
            border-radius: 4px;
        }
        .table-condensed th,
        .table-condensed td {
            padding: 4px 5px;
        }
        .arrow {
            margin-left: 5px;
        }
        .table-condensed th {
            font-size: 14px;
            font-weight: bold;
        }
        .table .form-control {
            width: 95%;
            min-width: 5rem;
        }
        .d-sm-table-cell {
            display: table-cell!important;
        }
    }
    @media (min-width: 767px) {
        .container-fluid {
            padding-right: 20px;
            padding-left: 20px;
        }
    }

</style>




<div id="app" class="container-fluid" v-cloak>
    <div class="alert mt-2" :class="{'alert-warning':true}" v-if="status.message">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="alert-heading" if="status.title">{{ status.title }}</h4>
        {{ status.message }}
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <h3><?php echo _('Dashboards') ?></h3>
            <button class="btn btn-light ml-3" @click.prevent="addNew"><?php echo _('New') ?>  <svg class="icon"><use xlink:href="#icon-plus"></use></svg> </button>
        </div>
        <form v-if="gridData.length > 0" id="search" class="form-inline position-relative mb-0">
            <div class="form-group">
                <input id="search-box" name="query" v-model="searchQuery" type="search" class="form-control input-medium mb-0" aria-describedby="searchHelp" placeholder="<?php echo _('Search') ?>" title="<?php echo _('Search the data by any column') ?>">
                <button id="searchclear" @click.prevent="searchQuery = ''"style="right:0" class="btn btn-link position-absolute" :class="{'d-none':searchQuery.length===0}"><svg class="icon"><use xlink:href="#icon-close"></use></svg></button>
            </div>
        </form>
    </div>

    <!-- custom component to display grid data-->
    <grid-data :grid-data="gridData" :columns="gridColumns" default-sort="id"
        :filter-key="searchQuery" :status="status"
        @update:total="status=arguments[0]"
        v-if="gridData.length > 0"
    >
    </grid-data>
</div>





<script src="<?php echo $path; ?>Modules/dashboard/dashboard.js"></script>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
<!-- <script src="/emoncms/Modules/config/vue.js"></script> -->

<script src="<?php echo $path; ?>Lib/misc/gettext.js"></script>
<script>
    /**
     * return plain js object with gettext translated strings
     * @return object
     */
    function getTranslations(){
        return {
            'Error': "<?php echo _('Error') ?>",
            'Save': "<?php echo _('Save') ?>",
            'Done': "<?php echo _('Done') ?>",
            'Error loading': "<?php echo _('Error loading') ?>",
            'Found %s entries': "<?php echo _('Found %s entries') ?>",
            'JS Error': "<?php echo _('JS Error') ?>",
            'Reload': "<?php echo _('Reload') ?>",
            'Loading': "<?php echo _('Loading') ?>…",
            'Saving': "<?php echo _('Saving') ?>…",
            'Label this dashboard with a name': "<?php echo _('Label this dashboard with a name') ?>",
            'Must be unique. Short title to use in URL.\neg \"roof-solar\"': "<?php echo _('Must be unique. Short title to use in URL.\neg \"roof-solar\"') ?>",
            'Adds a \"Default Dashboard\" bookmark in the sidebar.\nAlso visible at \"dashboard/view\"': "<?php echo _('Adds a \"Default Dashboard\" bookmark in the sidebar.\nAlso visible at \"dashboard/view\"') ?>",
            'Allow this Dashboard to be viewed by anyone': "<?php echo _('Allow this Dashboard to be viewed by anyone') ?>",
            'Clone the layout of this dashboard to a new Dashboard': "<?php echo _('Clone the layout of this dashboard to a new Dashboard') ?>",
            'Edit this dashboard layout': "<?php echo _('Edit this dashboard layout') ?>",
            'Delete this dashboard': "<?php echo _('Delete this dashboard') ?>…",
            'View this dashboard': "<?php echo _('View this dashboard') ?>…",
            'Edit Layout': "<?php echo _('Edit Layout') ?>",
            'No dashboards created': "<?php echo _('No dashboards created') ?>",
            'Maybe you would like to add your first dashboard using the button bellow.': "<?php echo _('Maybe you would like to add your first dashboard using the button bellow.') ?>"
        }
    }
</script>



<!-- START GRIDJS INCLUDE -------------------------------------------------------------- -->
<?php echo $gridjs ?>
<!-- END GRIDJS INCLUDE ---------------------------------------------------------------- -->



<script>
    // debugging functions
    var _DEBUG_ = false; // output debug messages
    var _debug = {
        log: function(){
            if(typeof _DEBUG_ !== 'undefined' && _DEBUG_) {
                console.trace.apply(this,arguments);
            }
        },
        error: function(){
            if(typeof _DEBUG_ !== 'undefined' && _DEBUG_) {
                console.error('Error')
                console.trace.apply(this, arguments);
            } else {
                console.log(arguments[0],'Set _debug=true to see more')
                console.log(this.error.caller)
            }
        }
    }

    // load list of dashboards via api and display as grid with editable fields
    // this uses vuejs to manage the app state
    // @see: https://vuejs.org/

    var app = new Vue({
        el: "#app",
        data: {
            wait: 800, // time to wait before sending data
            timeouts: {},
            classes: { // css class names
                success: 'success',
                error: 'error',
                warning: 'warning',
                fade: 'fade',
                buttonActive: 'btn-primary',
                button: 'btn-light'
            },
            statusData: { // store app status information
                title: '',
                message: '',
                fade: false,
                success: true,
                total: 0
            },
            searchQuery: "", // search string
            gridData: [{ // variable to store api response. example of api response items
                "id": 0,
                "name": "",
                "alias": "",
                "showdescription": "",
                "description": "",
                "main": false,
                "published": false,
                "public": false
            }], 
            gridColumns: { // columns to show with all properties
                /**
                 * @property Boolean sort - allow column to be sorted
                 * @property Boolean input - column item is editable
                 * @property Boolean noHeader - header title not to be shown
                 * @property Boolean link - column item is a link
                 * @property Boolean hideNarrow - hide column on smaller devices (.d-sm-block)
                 * @property String title - text to show to user as tooltip
                 * @property String label - re-label the column title
                 * @property String icon - display icon as column item (in a button or link)
                 * @property Function handler - if column item triggers an event, trigger this function
                 */ 

                id: {
                    sort: true
                },
                name: {
                    sort: true,
                    input: true,
                    title: _('Label this dashboard with a name'),
                    handler: function(event, item, property, value, success, error, always) {
                        try {
                            let vm = app;
                            let changed = true;
                            let timeout_key = item.id+'_'+property;
                            property = 'name';

                            vm.gridData.forEach(function(row, i) {
                                if (row.id === item.id && row[property] === value) {
                                    changed = false
                                }
                            })
                            if (!changed) {
                                // do nothing if content unchanged
                                window.clearTimeout(vm.timeouts[timeout_key]);
                            } else {
                                vm.Set_field_delayed(event, item, property, value, success, error, always)
                            }
                        } catch (error) {
                            _debug.error (_('JS Error'), property, error, arguments);
                        }
                    },
                    messages: {
                        success: _('Saved'),
                        error: _('Error'),
                        always: _('Done')
                    }
                },
                alias: {
                    sort: true,
                    input: true,
                    title: _('Must be unique. Short title to use in URL.\neg \"roof-solar\"'),
                    handler: function(event, item, property, value, success, error, always) {
                        try {
                            let vm = app;
                            let changed = true;
                            let unique = true;
                            let timeout_key = item.id+'_'+property;
                            let container = event.target.parentNode.parentNode;
                            let feedback = event.target.parentNode.querySelector('.help-inline')

                            vm.gridData.forEach(function(row, i) {
                                if (row.id === item.id && row[property] === value) {
                                    changed = false
                                }
                                if (row.id !== item.id && row[property] === value && value.length > 0) {
                                    unique = false;
                                }
                            })
                            if (!changed) {
                                // do nothing if content unchanged
                                window.clearTimeout(vm.timeouts[timeout_key]);
                            } else if(!unique) {
                                container.classList.add('error')
                                feedback.innerText = _('Not unique');
                                window.clearTimeout(vm.timeouts[timeout_key]);
                            } else {
                                success2 = function(event, data) {
                                    if (typeof success === 'function') {
                                        success.call(this, arguments);
                                    }
                                    // update the app datastore
                                    if (data.hasOwnProperty(property)) {
                                        item[property] = data[property];
                                        item.view = path + 'dashboard/view';
                                        item.view += item.alias.length > 0 ? '/' + item.alias: '?id=' + id;
                                    }
                                }
                                vm.Set_field_delayed(event, item, property, value, success2, error, always)
                            }
                        } catch (error) {
                            _debug.error (_('JS Error'), property, error, arguments);
                        }
                    },
                    messages: {
                        success: _('Saved'),
                        error: _('Error'),
                        always: _('Done')
                    }
                },
                main: {
                    sort: true,
                    icon: '#icon-star_border',
                    label: _('default'),
                    title: _('Adds a \"Default Dashboard\" bookmark in the sidebar.\nAlso visible at \"dashboard/view\"'),
                    handler: function(event, item) {
                        // "..there can be only one..!" -Highlander '86
                        try {
                            let vm = app;
                            let id = item.id
                            let field = 'main';
                            let value = !item[field];

                            dashboard_v2.set(field, id, value).then(function() {
                                // remove all default entries
                                vm.gridData.forEach(function(row, i){
                                    row[field] = false;
                                })
                                // set this one to default
                                item[field] = value;
                            });
                            
                        } catch (error) {
                            _debug.error (_('JS Error'), field, error, arguments);
                        }
                    }
                },
                public: {
                    sort: true,
                    icon: '#icon-earth',
                    title: _('Allow this Dashboard to be viewed by anyone'),
                    handler: function(event, item) {
                        // toggle public status
                        try {
                            let id = item.id
                            let field = 'public';
                            let value = !item[field];
                            // only modify view on success
                            dashboard_v2.set(field, id, value).then(function() {
                                item[field] = value;
                            });
                        } catch (error) {
                            _debug.error (_('JS Error'), field, error, arguments);
                        }
                    }
                },
                clone: {
                    icon: '#icon-content_copy',
                    noHeader: true,
                    title: _('Clone the layout of this dashboard to a new Dashboard'),
                    handler: function(event, item) {
                        // clone item
                        try {
                            let clone = Vue.util.extend({}, item);
                            let self = app;
                            dashboard_v2.clone(item.id).then(
                            function(insert_id){
                                clone.id = insert_id;
                                clone.name += ' clone';
                                clone.main = false;
                                clone.public = false;
                                clone.alias = '';
                                self.gridData.push(clone);
                            }, function(){
                                // @todo: handle error
                            })
                        } catch (error) {
                            _debug.error (_('JS Error'), field, error, arguments);
                        }
                    }
                },
                edit: {
                    icon: '#icon-cog',
                    noHeader: true,
                    link: true,
                    label: _('Edit Layout'),
                    title: _('Edit this dashboard layout'),
                    hideNarrow: true
                },
                delete: {
                    icon: '#icon-bin',
                    noHeader: true,
                    title: _('Delete this dashboard'),
                    handler: function(event, item){
                        // delete item
                        try {
                            let title = _('Delete "%s"').replace('%s',item.name);
                            let question = [
                                title,
                                _("Deleting a dashboard is permanent"),
                                "\n",
                                _("Are you sure you want to delete ?")
                            ]
                            let max = 0;
                            question.forEach(function(item){
                                if (item.length > max) max = item.length;
                            })
                            question.splice(1,0,'―'.repeat(max/1.9));
                            let confirmation = question.join("\n");

                            if(confirm(confirmation)){
                                var self = app;
                                dashboard_v2.remove(item.id)
                                .then(function(){
                                    var index = self.gridData.indexOf(item)
                                    self.gridData.splice(index, 1);
                                })
                            }
                        } catch (error) {
                            _debug.error (_('JS Error'), error, arguments);
                        }
                    }
                },
                view: {
                    icon: '#icon-arrow_forward',
                    noHeader: true,
                    link: true,
                    title: _('View this dashboard'),
                    hideNarrow: true
                }
            }

        },
        computed: {
            status: {
                get: function() {
                    return this.statusData
                },
                set: function(value){
                    let status = JSON.parse(JSON.stringify(this.statusData))
                    status.title = ''
                    status.message = ''
                    this.statusData = status;

                    switch (typeof value) {
                        case 'object':
                            this.statusData = value
                            break;
                        case 'number':
                            this.statusData.total = value
                            this.statusData.title =  _('Found %s entries').replace('%s', value)
                            break;
                        case 'string':
                            this.statusData.title = value
                            break;
                    }
                }
            }
        },
        mounted: function () {
            // on load request server data
            this.update();
        },
        created: function () {
            // pass on root event handler to relevant function
            this.$root.$on('event:handler', function(event, item, property, value, success, error, always) {
                success2 = function() {
                    if (typeof success === 'function') success.call(this, arguments);
                    item[property] = value;// set the global dataStore
                }
                if(this.gridColumns[property] && this.gridColumns[property].handler && typeof this.gridColumns[property].handler === 'function') {
                    this.gridColumns[property].handler(event, item, property, value, success2, error, always);
                }
            });
        },
        methods : {
            // ----------
            // UTILITIES
            // ----------

            /**
             * Delayed submission of data to server
             * 
             * wait for pause in user input before sending data to server
             */
            Set_field_delayed: function(event, item, property, value, success_callback, error_callback, always_callback) {
                var vm = this;
                var timeout_key = item.id+'_'+property;
                window.clearTimeout(vm.timeouts[timeout_key]);
                vm.timeouts[timeout_key] = window.setTimeout( function() {
                    vm.Notify('…')
                    // set the property and call the callbacks
                    success = function(data, success_message, xhr) {
                        // execute on successful save
                        _debug.log (_('set_field_delayed() SUCCESS'), success_message, arguments);
                        window.clearTimeout(vm.timeouts[timeout_key])
                        vm.Notify(data.message)
                        if (typeof success_callback == 'function') {
                            success_callback(event,data);
                        }
                    }
                    error = function(xhr, error_message) {
                        // execute on save failure
                        vm.Notify(error_message, true);
                        
                        if (typeof error_callback == 'function') {
                            error_callback(event)
                        }
                        if (typeof _DEBUG_ !== 'undefined' && _DEBUG_) {
                            throw ['500_'+property, error_message].join(' ');
                        }
                    }
                    always = function () {
                        // execute this function on success or failure
                        if (typeof always_callback === 'function') always_callback()
                    }
                    // call the dashboard.set() and supply promise function
                    dashboard_v2.set(property, item.id, value).then(success, error).then(always)
                }, vm.wait);

            },
            /**
             * modify app status object and display feedback to user
             * @param mixed status [String] === title only | [Number] === total only | [Object] === overwrite all 
             */
            Notify: function(status, persist) {
                // 
                vm = this
                if (typeof status === 'number') {
                    status = Object.assign({}, vm.status, {total:status, fade: true})
                    persist = true
                } else if (typeof status === 'string') {
                    status = Object.assign({}, vm.status, {title:status, fade: true})
                } else if (typeof status !== 'object') {
                    status = Object.assign({}, vm.status, {fade: true})
                } 
                // stop previous delay
                window.clearTimeout(this.statusTimeout);
                if(!persist) {
                    vm.status = status
                    // wait then reset status 
                    this.statusTimeout = window.setTimeout(function(){
                        // reset to show just the total
                        vm.status = vm.status.total
                    }, this.wait * 3);
                } else {
                    if (typeof status === 'object') status.fade = false
                    vm.status = status
                }
            },
            addNew: function() {
                let vm = this;
                dashboard_v2.add().then(function() {
                    vm.update(_('Added'));
                })
            },
            update: function(message) {
                let vm = this;
                message = message ? message: _('Loading');
                vm.Notify(message, true)
                dashboard_v2.list().then(
                    function(data){
                        // handle success - populate gridData[] array
                        // add urls for edit and view
                        data.forEach(function(v,i){
                            let id = data[i].id;
                            data[i].edit = path + 'dashboard/edit?id=' + id;
                            data[i].view = path + 'dashboard/view';
                            data[i].view += v.alias.length > 0 ? '/' + v.alias: '?id=' + id;
                        });
                        if(data.length === 0) {
                            vm.gridData = [];
                            vm.Notify({
                                'title': _('No dashboards created'),
                                'message': _('Maybe you would like to add your first dashboard using the button bellow.')
                            }, true)
                        } else {
                            vm.gridData = data;
                        }

                    }, function(xhr,message) {
                        // handle error - notify user
                        vm.Notify = ({
                            success: false,
                            title: _('Error loading.'),
                            message: message,
                            total: 0,
                            url: this.url
                        }, true)
                    }
                )
            }
        }
    });

</script>