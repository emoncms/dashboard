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

    <!-- Dashboard grid table -->
    <table v-if="gridData.length > 0" class="table table-sm table-hover table-condensed">
        <caption v-if="status" class="text-muted muted text-left mt-3">{{ status.title }}</caption>
        <thead>
            <tr>
                <!-- ID Column -->
                <th :class="{ active: sortKey == 'id' }" @click="sortBy('id')">
                    <a href="#" class="text-body d-flex align-items-center">
                        <span>ID</span>
                        <span class="arrow" :class="sortOrders.id > 0 ? 'asc' : 'dsc'"></span>
                    </a>
                </th>
                <!-- Name Column -->
                <th :class="{ active: sortKey == 'name' }" @click="sortBy('name')">
                    <a href="#" class="text-body d-flex align-items-center pl-2">
                        <span>Name</span>
                        <span class="arrow" :class="sortOrders.name > 0 ? 'asc' : 'dsc'"></span>
                    </a>
                </th>
                <!-- Alias Column -->
                <th :class="{ active: sortKey == 'alias' }" @click="sortBy('alias')">
                    <a href="#" class="text-body d-flex align-items-center pl-2">
                        <span>Alias</span>
                        <span class="arrow" :class="sortOrders.alias > 0 ? 'asc' : 'dsc'"></span>
                    </a>
                </th>
                <!-- Default Column -->
                <th :class="{ active: sortKey == 'main' }" @click="sortBy('main')">
                    <a href="#" class="text-body d-flex align-items-center">
                        <span>{{ translations['default'] | capitalize }}</span>
                        <span class="arrow" :class="sortOrders.main > 0 ? 'asc' : 'dsc'"></span>
                    </a>
                </th>
                <!-- Public Column -->
                <th :class="{ active: sortKey == 'public' }" @click="sortBy('public')">
                    <a href="#" class="text-body d-flex align-items-center">
                        <span>Public</span>
                        <span class="arrow" :class="sortOrders.public > 0 ? 'asc' : 'dsc'"></span>
                    </a>
                </th>
                <!-- Published Column -->
                <th :class="{ active: sortKey == 'published' }" @click="sortBy('published')">
                    <a href="#" class="text-body d-flex align-items-center">
                        <span>Published</span>
                        <span class="arrow" :class="sortOrders.published > 0 ? 'asc' : 'dsc'"></span>
                    </a>
                </th>
                <!-- Actions -->
                <th><!-- Clone --></th>
                <th class="d-none d-sm-table-cell">{{ translations['Edit Layout'] }}</th>
                <th><!-- Delete --></th>
                <th class="d-none d-sm-table-cell"><!-- View --></th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="entry in filteredData" :key="entry.id">
                <!-- ID Column -->
                <td>{{ entry.id }}</td>
                
                <!-- Name Column (Editable) -->
                <td>
                    <div class="control-group">
                        <div class="controls">
                            <input @input="updateName($event, entry)" 
                                   :value="entry.name" 
                                   type="text" 
                                   class="form-control border-0 m-0" 
                                   @keyup.enter="$event.target.blur()" 
                                   :title="'#' + entry.id + ' NAME\n' + translations['Label this dashboard with a name']">
                            <span class="help-inline d-none d-sm-block text-right"></span>
                        </div>
                    </div>
                </td>
                
                <!-- Alias Column (Editable) -->
                <td>
                    <div class="control-group">
                        <div class="controls">
                            <input @input="updateAlias($event, entry)" 
                                   :value="entry.alias" 
                                   type="text" 
                                   class="form-control border-0 m-0" 
                                   @keyup.enter="$event.target.blur()" 
                                   :title="'#' + entry.id + ' ALIAS\n' + translations['Must be unique. Short title to use in URL.\neg roof-solar']">
                            <span class="help-inline d-none d-sm-block text-right"></span>
                        </div>
                    </div>
                </td>
                
                <!-- Default Toggle -->
                <td>
                    <button @click="toggleMain(entry)"
                            class="btn btn-sm-md btn-small"
                            :class="entry.main ? 'btn-primary active' : 'btn-light'"
                            :title="'#' + entry.id + ' MAIN\n' + translations['Adds a Default Dashboard bookmark in the sidebar.\nAlso visible at dashboard/view']">
                        <svg class="icon"><use xlink:href="#icon-star_border"></use></svg>
                    </button>
                </td>
                
                <!-- Public Toggle -->
                <td>
                    <button @click="togglePublic(entry)"
                            class="btn btn-sm-md btn-small"
                            :class="entry.public ? 'btn-primary active' : 'btn-light'"
                            :title="'#' + entry.id + ' PUBLIC\n' + translations['Allow this Dashboard to be viewed by anyone']">
                        <svg class="icon"><use xlink:href="#icon-earth"></use></svg>
                    </button>
                </td>
                
                <!-- Published Toggle -->
                <td>
                    <button @click="togglePublished(entry)"
                            class="btn btn-sm-md btn-small"
                            :class="entry.published ? 'btn-primary active' : 'btn-light'"
                            :title="'#' + entry.id + ' PUBLISHED\n' + translations['Allow this Dashboard on the menu']">
                        <svg class="icon"><use xlink:href="#icon-dashboard"></use></svg>
                    </button>
                </td>
                
                <!-- Clone Button -->
                <td>
                    <button @click="cloneDashboard(entry)"
                            class="btn btn-light btn-sm-md btn-small"
                            :title="'#' + entry.id + ' CLONE\n' + translations['Clone the layout of this dashboard to a new Dashboard']">
                        <svg class="icon"><use xlink:href="#icon-content_copy"></use></svg>
                    </button>
                </td>
                
                <!-- Edit Link -->
                <td class="d-none d-sm-table-cell">
                    <a class="btn btn-light btn-sm-md btn-small"
                       :title="'#' + entry.id + ' EDIT\n' + translations['Edit this dashboard layout']"
                       :href="entry.edit">
                        <svg class="icon"><use xlink:href="#icon-cog"></use></svg>
                    </a>
                </td>
                
                <!-- Delete Button -->
                <td>
                    <button @click="deleteDashboard(entry)"
                            class="btn btn-light btn-sm-md btn-small"
                            :title="'#' + entry.id + ' DELETE\n' + translations['Delete this dashboard']">
                        <svg class="icon"><use xlink:href="#icon-bin"></use></svg>
                    </button>
                </td>
                
                <!-- View Link -->
                <td class="d-none d-sm-table-cell">
                    <a class="btn btn-light btn-sm-md btn-small"
                       :title="'#' + entry.id + ' VIEW\n' + translations['View this dashboard']"
                       :href="entry.view">
                        <svg class="icon"><use xlink:href="#icon-arrow_forward"></use></svg>
                    </a>
                </td>
            </tr>
        </tbody>
    </table>
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
            'Allow this Dashboard on the menu': "<?php echo tr('Allow this Dashboard on the menu') ?>",
            'Clone the layout of this dashboard to a new Dashboard': "<?php echo tr('Clone the layout of this dashboard to a new Dashboard') ?>",
            'Edit this dashboard layout': "<?php echo tr('Edit this dashboard layout') ?>",
            'Delete this dashboard': "<?php echo tr('Delete this dashboard') ?>…",
            'View this dashboard': "<?php echo tr('View this dashboard') ?>…",
            'Edit Layout': "<?php echo tr('Edit Layout') ?>",
            'default': "<?php echo tr('Default') ?>",
            'Not unique': "<?php echo tr('Not unique') ?>",
            'Delete "%s"': "<?php echo tr('Delete \"%s\"') ?>",
            'Deleting a dashboard is permanent': "<?php echo tr('Deleting a dashboard is permanent') ?>",
            'Are you sure you want to delete ?': "<?php echo tr('Are you sure you want to delete ?') ?>",
            'Added': "<?php echo tr('Added') ?>"
        }
    }
</script>






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

    var app = new Vue({
        el: "#app",
        data: {
            wait: 800,
            timeouts: {},
            statusData: { title: '', message: '', fade: false, success: true, total: 0 },
            searchQuery: "",
            gridData: [],
            sortKey: "id", // Default sort by id
            sortOrders: {
                id: 1,
                name: 1,
                alias: 1,
                main: 1,
                public: 1,
                published: 1
            },
            translations: getTranslations()
        },
        filters: {
            capitalize: function(str) {
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
        },
        computed: {
            status: {
                get() { return this.statusData; },
                set(value) {
                    const status = { ...this.statusData, title: '', message: '' };
                    
                    if (typeof value === 'object') Object.assign(status, value);
                    else if (typeof value === 'number') {
                        status.total = value;
                        status.title = this.translations['Found %s entries'].replace('%s', value);
                    }
                    else if (typeof value === 'string') status.title = value;
                    
                    this.statusData = status;
                }
            },
            filteredData: function() {
                var sortKey = this.sortKey;
                var filterKey = this.searchQuery && this.searchQuery.toLowerCase();
                var order = this.sortOrders[sortKey] || 1;
                var data = this.gridData;
                
                if (filterKey) {
                    data = data.filter(function(row) {
                        return Object.keys(row).some(function(key) {
                            return (
                                String(row[key])
                                .toLowerCase()
                                .indexOf(filterKey) > -1
                            );
                        });
                    });
                }
                
                if (sortKey) {
                    data = data.slice().sort(function(a, b) {
                        a = a[sortKey];
                        b = b[sortKey];
                        return (a === b ? 0 : a > b ? 1 : -1) * order;
                    });
                }
                
                return data;
            }
        },
        watch: {
            filteredData: function(results) {
                this.status = results.length;
            }
        },
        mounted() { 
            this.update(); 
        },
        methods: {
            sortBy: function(key) {
                this.sortKey = key;
                this.sortOrders[key] = this.sortOrders[key] * -1;
            },

            // Input field feedback helper
            showInputFeedback: function(input, message, type = 'success') {
                const container = input.parentNode.parentNode;
                const feedback = input.parentNode.querySelector('.help-inline');
                if (feedback) {
                    // Reset classes
                    container.classList.remove('success', 'error', 'warning');
                    feedback.innerText = '';
                    
                    // Add feedback
                    if (message) {
                        container.classList.add(type);
                        feedback.innerText = message;
                        feedback.classList.add('fade');
                        
                        // Auto-clear after delay
                        setTimeout(() => {
                            container.classList.remove('success', 'error', 'warning');
                            feedback.innerText = '';
                            feedback.classList.remove('fade');
                        }, this.wait * 2.3);
                    }
                }
            },

            // Update dashboard name
            updateName: function(event, item) {
                const value = event.target.value;
                const changed = item.name !== value;
                
                if (!changed) return;
                
                clearTimeout(this.timeouts[`${item.id}_name`]);
                this.timeouts[`${item.id}_name`] = setTimeout(() => {
                    this.notify('…');
                    
                    dashboard_v2.set('name', item.id, value).then(
                        (data) => {
                            item.name = value;
                            this.showInputFeedback(event.target, this.translations['Save']);
                            this.notify(data.message);
                        },
                        (xhr, message) => {
                            this.showInputFeedback(event.target, this.translations['Error'], 'error');
                            this.notify(message, true);
                        }
                    );
                }, this.wait);
            },

            // Update dashboard alias
            updateAlias: function(event, item) {
                const value = event.target.value;
                const changed = item.alias !== value;
                
                if (!changed) return;
                
                // Check uniqueness
                const unique = !this.gridData.some(row => 
                    row.id !== item.id && row.alias === value && value.length > 0
                );
                
                if (!unique) {
                    this.showInputFeedback(event.target, this.translations['Not unique'], 'error');
                    return;
                }
                
                clearTimeout(this.timeouts[`${item.id}_alias`]);
                this.timeouts[`${item.id}_alias`] = setTimeout(() => {
                    this.notify('…');
                    
                    dashboard_v2.set('alias', item.id, value).then(
                        (data) => {
                            item.alias = data.alias || value;
                            item.view = this.buildViewUrl(item);
                            this.showInputFeedback(event.target, this.translations['Save']);
                            this.notify(data.message);
                        },
                        (xhr, message) => {
                            this.showInputFeedback(event.target, this.translations['Error'], 'error');
                            this.notify(message, true);
                        }
                    );
                }, this.wait);
            },

            // Toggle main dashboard
            toggleMain: function(item) {
                const value = !item.main;
                dashboard_v2.set('main', item.id, value).then(() => {
                    // Clear all other main flags
                    this.gridData.forEach(row => row.main = false);
                    item.main = value;
                });
            },

            // Toggle public status
            togglePublic: function(item) {
                const value = !item.public;
                dashboard_v2.set('public', item.id, value).then(() => {
                    item.public = value;
                });
            },

            // Toggle published status
            togglePublished: function(item) {
                const value = !item.published;
                dashboard_v2.set('published', item.id, value).then(() => {
                    item.published = value;
                });
            },

            // Clone dashboard
            cloneDashboard: function(item) {
                dashboard_v2.clone(item.id).then(insertId => {
                    const clone = {
                        ...item,
                        id: insertId,
                        name: item.name + ' clone',
                        main: false,
                        public: false,
                        published: false,
                        alias: '',
                        edit: `${path}dashboard/edit?id=${insertId}`,
                        view: `${path}dashboard/view?id=${insertId}`
                    };
                    this.gridData.push(clone);
                });
            },

            // Delete dashboard
            deleteDashboard: function(item) {
                const confirmation = this.buildDeleteConfirmation(item.name);
                if (confirm(confirmation)) {
                    dashboard_v2.remove(item.id).then(() => {
                        const index = this.gridData.indexOf(item);
                        this.gridData.splice(index, 1);
                    });
                }
            },

            buildDeleteConfirmation(name) {
                const title = this.translations['Delete "%s"'].replace('%s', name);
                const question = [
                    title,
                    this.translations["Deleting a dashboard is permanent"],
                    "\n",
                    this.translations["Are you sure you want to delete ?"]
                ];
                const maxLength = Math.max(...question.map(item => item.length));
                question.splice(1, 0, '―'.repeat(maxLength / 1.9));
                return question.join("\n");
            },

            buildViewUrl(item) {
                const baseUrl = path + 'dashboard/view';
                return item.alias.length > 0 ? `${baseUrl}/${item.alias}` : `${baseUrl}?id=${item.id}`;
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
                    this.update({ title: this.translations['Added'] });
                });
            },

            update(message) {
                message = message || this.translations['Loading'];
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
                            title: this.translations['Error loading'],
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
