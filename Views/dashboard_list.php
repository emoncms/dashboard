<?php
defined('EMONCMS_EXEC') or die('Restricted access');
?>

<!-- include dashboard_list.css -->
<link href="<?php echo $path; ?>Modules/dashboard/Views/dashboard_list.css?ver=<?php echo $js_css_version; ?>" rel="stylesheet">

<div id="app" class="container-fluid" v-cloak>
    <div class="alert mt-2" :class="{'alert-warning':true}" v-if="gridData.length === 0">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="alert-heading" if="status.title"><?php echo tr('No dashboards created') ?></h4>
        <?php echo tr('Maybe you would like to add your first dashboard using the button below&hellip;') ?>
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <h3><?php echo tr('Dashboards') ?></h3>
            <button class="btn btn-light ml-3" @click.prevent="addNew"><?php echo tr('New') ?>  <svg class="icon"><use xlink:href="#icon-plus"></use></svg> </button>
        </div>
        <form v-if="gridData.length > 0" id="search" class="form-inline position-relative mb-0">
            <div class="form-group">
                <input id="search-box" name="query" v-model="searchQuery" type="search" class="form-control input-medium mb-0" aria-describedby="searchHelp" placeholder="<?php echo tr('Search') ?>" title="<?php echo tr('Search the data by any column') ?>">
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





<script src="<?php echo $path; ?>Modules/dashboard/dashboard.js?v=1"></script>
<script src="<?php echo $path; ?>Lib/vue.min.js"></script>
<script src="<?php echo $path; ?>Lib/misc/gettext.js?v=2"></script>
<script>
    /**
     * return plain js object with gettext translated strings
     * @return object
     */
    function getTranslations(){
        return {
            'Error': "<?php echo tr('Error') ?>",
            'Save': "<?php echo tr('Save') ?>",
            'Done': "<?php echo tr('Done') ?>",
            'Error loading': "<?php echo tr('Error loading') ?>",
            'Found %s entries': "<?php echo tr('Found %s entries') ?>",
            'JS Error': "<?php echo tr('JS Error') ?>",
            'Reload': "<?php echo tr('Reload') ?>",
            'Loading': "<?php echo tr('Loading') ?>…",
            'Saving': "<?php echo tr('Saving') ?>…",
            'Label this dashboard with a name': "<?php echo tr('Label this dashboard with a name') ?>",
            'Must be unique. Short title to use in URL.\neg \"roof-solar\"': "<?php echo tr('Must be unique. Short title to use in URL.\neg \"roof-solar\"') ?>",
            'Adds a \"Default Dashboard\" bookmark in the sidebar.\nAlso visible at \"dashboard/view\"': "<?php echo tr('Adds a \"Default Dashboard\" bookmark in the sidebar.\nAlso visible at \"dashboard/view\"') ?>",
            'Allow this Dashboard to be viewed by anyone': "<?php echo tr('Allow this Dashboard to be viewed by anyone') ?>",
            'Clone the layout of this dashboard to a new Dashboard': "<?php echo tr('Clone the layout of this dashboard to a new Dashboard') ?>",
            'Edit this dashboard layout': "<?php echo tr('Edit this dashboard layout') ?>",
            'Delete this dashboard': "<?php echo tr('Delete this dashboard') ?>…",
            'View this dashboard': "<?php echo tr('View this dashboard') ?>…",
            'Edit Layout': "<?php echo tr('Edit Layout') ?>"
        }
    }
</script>



<!-- START GRIDJS INCLUDE -------------------------------------------------------------- -->
<?php echo $gridjs ?>
<!-- END GRIDJS INCLUDE ---------------------------------------------------------------- -->



<script>
    // debugging functions
    var _DEBUG_ = false;
    var _debug = {
        log: function(){ if(_DEBUG_) console.trace.apply(this,arguments); },
        error: function(){
            if(_DEBUG_) {
                console.error('Error');
                console.trace.apply(this, arguments);
            } else {
                console.log(arguments[0],'Set _debug=true to see more');
                console.log(this.error.caller);
            }
        }
    }

    // Extract common translations
    const translations = getTranslations();
    
    // Common column configuration factory
    function createColumnConfig(type, options = {}) {
        const configs = {
            editable: {
                sort: true,
                input: true,
                messages: {
                    success: translations['Save'],
                    error: translations['Error'],
                    always: translations['Done']
                }
            },
            toggle: {
                sort: true
            },
            action: {
                noHeader: true
            }
        };
        return Object.assign({}, configs[type] || {}, options);
    }

    var app = new Vue({
        el: "#app",
        data: {
            wait: 800,
            timeouts: {},
            classes: {
                success: 'success', error: 'error', warning: 'warning',
                fade: 'fade', buttonActive: 'btn-primary', button: 'btn-light'
            },
            statusData: { title: '', message: '', fade: false, success: true, total: 0 },
            searchQuery: "",
            gridData: [],
            gridColumns: {} // Initialize as empty object
        },
        computed: {
            status: {
                get() { return this.statusData; },
                set(value) {
                    const status = { ...this.statusData, title: '', message: '' };
                    
                    if (typeof value === 'object') Object.assign(status, value);
                    else if (typeof value === 'number') {
                        status.total = value;
                        status.title = translations['Found %s entries'].replace('%s', value);
                    }
                    else if (typeof value === 'string') status.title = value;
                    
                    this.statusData = status;
                }
            }
        },
        mounted() { 
            this.initializeGridColumns();
            this.update(); 
        },
        created() {
            this.$root.$on('event:handler', (event, item, property, value, success, error, always) => {
                const success2 = () => {
                    if (typeof success === 'function') success.call(this, arguments);
                    item[property] = value;
                };
                const handler = this.gridColumns[property]?.handler;
                if (handler && typeof handler === 'function') {
                    handler(event, item, property, value, success2, error, always);
                }
            });
        },
        methods: {
            initializeGridColumns() {
                this.gridColumns = {
                    id: { sort: true },
                    name: createColumnConfig('editable', {
                        title: translations['Label this dashboard with a name'],
                        handler: this.createFieldHandler('name')
                    }),
                    alias: createColumnConfig('editable', {
                        title: translations['Must be unique. Short title to use in URL.\neg \"roof-solar\"'],
                        handler: this.createAliasHandler()
                    }),
                    main: createColumnConfig('toggle', {
                        icon: '#icon-star_border',
                        label: translations['default'],
                        title: translations['Adds a \"Default Dashboard\" bookmark in the sidebar.\nAlso visible at \"dashboard/view\"'],
                        handler: this.createMainHandler()
                    }),
                    public: createColumnConfig('toggle', {
                        icon: '#icon-earth',
                        title: translations['Allow this Dashboard to be viewed by anyone'],
                        handler: this.createToggleHandler('public')
                    }),
                    published: createColumnConfig('toggle', {
                        icon: '#icon-dashboard',
                        title: translations['Allow this Dashboard on the menu'],
                        handler: this.createToggleHandler('published')
                    }),
                    clone: createColumnConfig('action', {
                        icon: '#icon-content_copy',
                        title: translations['Clone the layout of this dashboard to a new Dashboard'],
                        handler: this.handleClone
                    }),
                    edit: createColumnConfig('action', {
                        icon: '#icon-cog',
                        link: true,
                        label: translations['Edit Layout'],
                        title: translations['Edit this dashboard layout'],
                        hideNarrow: true
                    }),
                    delete: createColumnConfig('action', {
                        icon: '#icon-bin',
                        title: translations['Delete this dashboard'],
                        handler: this.handleDelete
                    }),
                    view: createColumnConfig('action', {
                        icon: '#icon-arrow_forward',
                        link: true,
                        title: translations['View this dashboard'],
                        hideNarrow: true
                    })
                };
            },

            createFieldHandler(property) {
                return (event, item, prop, value, success, error, always) => {
                    try {
                        const changed = !this.gridData.some(row => row.id === item.id && row[property] === value);
                        if (changed) {
                            this.setFieldDelayed(event, item, property, value, success, error, always);
                        }
                    } catch (err) {
                        _debug.error(translations['JS Error'], property, err, arguments);
                    }
                };
            },

            createAliasHandler() {
                return (event, item, property, value, success, error, always) => {
                    try {
                        const changed = !this.gridData.some(row => row.id === item.id && row[property] === value);
                        const unique = !this.gridData.some(row => row.id !== item.id && row[property] === value && value.length > 0);
                        
                        if (!changed) return;
                        
                        if (!unique) {
                            this.showValidationError(event.target, translations['Not unique']);
                            return;
                        }

                        const success2 = (event, data) => {
                            if (typeof success === 'function') success.call(this, arguments);
                            if (data.hasOwnProperty(property)) {
                                item[property] = data[property];
                                item.view = this.buildViewUrl(item);
                            }
                        };
                        this.setFieldDelayed(event, item, property, value, success2, error, always);
                    } catch (err) {
                        _debug.error(translations['JS Error'], property, err, arguments);
                    }
                };
            },

            createToggleHandler(field) {
                return (event, item) => {
                    try {
                        const value = !item[field];
                        dashboard_v2.set(field, item.id, value).then(() => {
                            item[field] = value;
                        });
                    } catch (err) {
                        _debug.error(translations['JS Error'], field, err, arguments);
                    }
                };
            },

            createMainHandler() {
                return (event, item) => {
                    try {
                        const field = 'main';
                        const value = !item[field];
                        dashboard_v2.set(field, item.id, value).then(() => {
                            this.gridData.forEach(row => row[field] = false);
                            item[field] = value;
                        });
                    } catch (err) {
                        _debug.error(translations['JS Error'], field, err, arguments);
                    }
                };
            },

            handleClone(event, item) {
                try {
                    const clone = { ...item };
                    dashboard_v2.clone(item.id).then(insertId => {
                        Object.assign(clone, {
                            id: insertId,
                            name: item.name + ' clone',
                            main: false,
                            public: false,
                            alias: ''
                        });
                        this.gridData.push(clone);
                    });
                } catch (err) {
                    _debug.error(translations['JS Error'], err, arguments);
                }
            },

            handleDelete(event, item) {
                try {
                    const confirmation = this.buildDeleteConfirmation(item.name);
                    if (confirm(confirmation)) {
                        dashboard_v2.remove(item.id).then(() => {
                            const index = this.gridData.indexOf(item);
                            this.gridData.splice(index, 1);
                        });
                    }
                } catch (err) {
                    _debug.error(translations['JS Error'], err, arguments);
                }
            },

            buildDeleteConfirmation(name) {
                const title = translations['Delete "%s"'].replace('%s', name);
                const question = [
                    title,
                    translations["Deleting a dashboard is permanent"],
                    "\n",
                    translations["Are you sure you want to delete ?"]
                ];
                const maxLength = Math.max(...question.map(item => item.length));
                question.splice(1, 0, '―'.repeat(maxLength / 1.9));
                return question.join("\n");
            },

            buildViewUrl(item) {
                const baseUrl = path + 'dashboard/view';
                return item.alias.length > 0 ? `${baseUrl}/${item.alias}` : `${baseUrl}?id=${item.id}`;
            },

            showValidationError(target, message) {
                const container = target.parentNode.parentNode;
                const feedback = target.parentNode.querySelector('.help-inline');
                container.classList.add('error');
                feedback.innerText = message;
            },

            setFieldDelayed(event, item, property, value, successCallback, errorCallback, alwaysCallback) {
                const timeoutKey = `${item.id}_${property}`;
                clearTimeout(this.timeouts[timeoutKey]);
                
                this.timeouts[timeoutKey] = setTimeout(() => {
                    this.notify('…');
                    
                    const success = (data, successMessage, xhr) => {
                        clearTimeout(this.timeouts[timeoutKey]);
                        this.notify(data.message);
                        if (typeof successCallback === 'function') successCallback(event, data);
                    };
                    
                    const error = (xhr, errorMessage) => {
                        this.notify(errorMessage, true);
                        if (typeof errorCallback === 'function') errorCallback(event);
                        if (_DEBUG_) throw `500_${property} ${errorMessage}`;
                    };
                    
                    const always = () => {
                        if (typeof alwaysCallback === 'function') alwaysCallback();
                    };
                    
                    dashboard_v2.set(property, item.id, value).then(success, error).then(always);
                }, this.wait);
            },

            notify(status, persist) {
                clearTimeout(this.statusTimeout);
                
                if (typeof status === 'number') {
                    status = { ...this.status, total: status, fade: true };
                    persist = true;
                } else if (typeof status === 'string') {
                    status = { ...this.status, title: status, fade: true };
                } else if (typeof status !== 'object') {
                    status = { ...this.status, fade: true };
                }
                
                this.status = status;
                
                if (!persist) {
                    this.statusTimeout = setTimeout(() => {
                        this.status = this.status.total;
                    }, this.wait * 3);
                } else if (typeof status === 'object') {
                    status.fade = false;
                }
            },

            addNew() {
                dashboard_v2.add().then(() => {
                    this.update({ title: translations['Added'] });
                });
            },

            update(message) {
                message = message || translations['Loading'];
                this.notify(message, true);
                
                dashboard_v2.list().then(
                    data => {
                        data.forEach(item => {
                            if (item) {
                                item.edit = `${path}dashboard/edit?id=${item.id}`;
                                item.alias = item.alias || "";
                                item.view = this.buildViewUrl(item);
                            }
                        });
                        this.gridData = data;
                    },
                    (xhr, message) => {
                        this.notify({
                            success: false,
                            title: translations['Error loading'],
                            message: message,
                            total: 0,
                            url: this.url
                        }, true);
                    }
                );
            }
        }
    });
</script>
