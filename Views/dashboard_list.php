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
    .form-control[type="search"]{
        width: 8rem;
    }
    .table .form-control{
        cursor: pointer;
        border: 1px solid transparent;
        box-shadow: none;
        width: 60%;
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
        .form-control[type="search"]{
            width: 206px;
        }
    }
    @media (min-width: 767px) {
        .container-fluid {
            padding-right: 20px;
            padding-left: 20px;
        }
    }

</style>




<div id="app" class="container-fluid">
    <div class="alert" :class="{'alert-warning':true}" v-if="status.message" v-cloak>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="alert-heading" if="status.title">{{ status.title }}</h4>
        {{ status.message }}
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <h3><?php echo _('Dashboards') ?> <svg class="icon text-info"><use xlink:href="#icon-dashboard"></use></svg> 
        <a v-if="gridData.length === 0" href="<?php echo $path ?>dashboard/list" class="btn btn-info btn-small btn-sm" :title="_('Reload') + '&hellip;'">
            <svg class="icon"><use xlink:href="#icon-spinner11"></use></svg>
        </a>
        </h3>
        <form id="search" class="form-inline position-relative mb-0">
            <div class="form-group">
                <input id="search-box" name="query" v-model="searchQuery" type="search" class="form-control mb-0" aria-describedby="searchHelp" placeholder="<?php echo _('Search') ?>" title="<?php echo _('Search the data by any column') ?>">
                <button id="searchclear" @click.prevent="searchQuery = ''"style="right:0" class="btn btn-link position-absolute" :class="{'d-none':searchQuery.length===0}"><svg class="icon"><use xlink:href="#icon-close"></use></svg></button>
            </div>
        </form>
    </div>

    <!-- custom component to display grid data-->
    <grid-data :grid-data="gridData" :columns="gridColumns"
        :filter-key="searchQuery" :caption="status.title"
        @update:total="status=arguments[0]"
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
            'Error loading': "<?php echo _('Error loading') ?>",
            'Found %s entries': "<?php echo _('Found %s entries') ?>",
            'JS Error': "<?php echo _('JS Error') ?>",
            'Reload': "<?php echo _('Reload') ?>",
            'Loading': "<?php echo _('Loading') ?>…",
            'Saving': "<?php echo _('Saving') ?>…",
            'Label this dashboard with a name': "<?php echo _('Label this dashboard with a name') ?>",
            'Short title to use in URL.\neg \"roof-solar\"': "<?php echo _('Short title to use in URL.\neg \"roof-solar\"') ?>",
            'Adds a \"Default Dashboard\" bookmark in the sidebar.\nAlso visible at \"dashboard/view\"': "<?php echo _('Adds a \"Default Dashboard\" bookmark in the sidebar.\nAlso visible at \"dashboard/view\"') ?>",
            'Allow this Dashboard to be viewed by anyone': "<?php echo _('Allow this Dashboard to be viewed by anyone') ?>",
            'Clone the layout of this dashboard to a new Dashboard': "<?php echo _('Clone the layout of this dashboard to a new Dashboard') ?>",
            'Edit this dashboard layout': "<?php echo _('Edit this dashboard layout') ?>",
            'Delete this dashboard': "<?php echo _('Delete this dashboard') ?>…",
            'View this dashboard': "<?php echo _('View this dashboard') ?>…",
            'Edit Layout': "<?php echo _('Edit Layout') ?>"
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
            statusData: {}, // store app status information
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
                    handler: function(event, item, property, value, success, error) {
                        try {
                            app.Set_field_delayed(event, item, property, value, success, error)
                        } catch (error) {
                            _debug.error (_('JS Error'), property, error, arguments);
                        }
                    }
                },
                alias: {
                    sort: true,
                    input: true,
                    title: _('Short title to use in URL.\neg \"roof-solar\"'),
                    handler: function(event, item, property, value, success, error) {
                        try {
                            app.Set_field_delayed(event, item, property, value, success, error)
                        } catch (error) {
                            _debug.error (_('JS Error'), field, error, arguments);
                        }
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
                            var vm = app;
                            var id = item.id
                            var field = 'main';
                            var value = !item[field];

                            // only modify view on success
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
                            var id = item.id
                            var field = 'public';
                            var value = !item[field];
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
            let vm = this;
            vm.Notify(_('Loading'), true)
            dashboard_v2.list().then(function(data){
                // handle success - populate gridData[] array
                // add urls for edit and view
                data.forEach(function(v,i){
                    let id = data[i].id;
                    data[i].view = path + 'dashboard/view?id=' + id;
                    data[i].edit = path + 'dashboard/edit?id=' + id;
                });
                vm.gridData = data;

            }, function(xhr,message){
                vm.Notify = ({
                    success: false,
                    title: _('Error loading.'),
                    message: message,
                    total: 0,
                    url: this.url
                }, true)
            })
            this._timeouts = {}
        },
        created: function () {
            // pass on root event handler to relevant function
            this.$root.$on('event:handler', function(event, item, property, value, success, error) {
                if (typeof success === 'function') {
                    success = function(){
                        item[property] = value;// set the global dataStore
                        success();
                    }
                } else {
                    success = function() {
                        item[property] = value;// set the global dataStore
                    }
                }
                // pass on the event to relevant function
                if(this.gridColumns[property] && this.gridColumns[property].handler && typeof this.gridColumns[property].handler === 'function') {
                    // call the fields' function, passing the dataGrid[] item that matches the index
                    this.gridColumns[property].handler(event, item, property, value, success, error);
                }
            })
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
            Set_field_delayed: function(event, item, property, value, success, error){
                var vm = this;
                var timeout_key = item.id+'_'+property;
                window.clearTimeout(vm._timeouts[timeout_key]);
                vm._timeouts[timeout_key] = window.setTimeout( function() {
                    vm.Notify('…')
                    dashboard_v2.set(property, item.id, value).then(
                        function(data, success_message, xhr){
                            _debug.log (_('SUCCESS'), success_message, arguments);
                            window.clearTimeout(vm._timeouts[timeout_key])
                            vm.Notify(_('Saved'))
                            if (typeof success == 'function') {
                                success(event)
                            }
                        },
                        function(xhr, error_message) {
                            vm.Notify(error_message)
                            if (typeof error == 'function') {
                                error(event)
                            }
                            throw ['500_'+property, error_message].join(' ');
                        }
                    )
                }, vm.wait);
            },
            /**
             * display feedback to user
             */
            Notify: function(status, persist) {
                // display message to user
                this.status = status
                vm = this
                // stop previous delay
                window.clearTimeout(this.statusTimeout);
                if(!persist) {
                    // wait until status is reset
                    this.statusTimeout = window.setTimeout(function(){
                        // reset to show the total
                        vm.status = vm.status.total;
                    }, this.wait * 3);
                }
            }
        }
    });

</script>