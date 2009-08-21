/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine', 'Tine.Setup');
 
/**
 * Setup Configuration Manager
 * 
 * @package Setup
 * 
 * @class Tine.Setup.ConfigManagerGridPanel
 * @extends Ext.FormPanel
 * 
 * NOTE: 
 * - For each section in the config file, a group in the form is added.
 * - Form names are constructed <section>_<subsection>... and transformed transparently by this component
 * - Enabling and Disabling of sections is handled automatically if you give the id 'setup-<section>-group' 
 *   to the checkboxToggle enabled fieldset
 */
Tine.Setup.ConfigManagerGridPanel = Ext.extend(Ext.FormPanel, {
    
    border: false,
    bodyStyle:'padding:5px 5px 0',
    labelAlign: 'left',
    labelSeparator: ':',
    labelWidth: 150,
    defaults: {
        xtype: 'fieldset',
        autoHeight: 'auto',
        defaults: {width: 300},
        defaultType: 'textfield'
    },
    
    autoScroll: true,
    
    // fake a store to satisfy grid panel
    store: {load: Ext.emptyFn},
    
    /**
     * save config and update setup registry
     */
    onSaveConfig: function() {
        if (this.getForm().isValid()) {
            var configData = this.form2config();
            
            this.loadMask.show();
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Setup.saveConfig',
                    data: Ext.util.JSON.encode(configData)
                },
                success: function(response) {
                    var regData = Ext.util.JSON.decode(response.responseText);
                    // replace some registry data
                    for (key in regData) {
                        if (key != 'status') {
                            Tine.Setup.registry.replace(key, regData[key]);
                        }
                    }
                    this.loadMask.hide();
                }
            });
        } else {
            Ext.Msg.alert(this.app.i18n._('Invalid configuration'), this.app.i18n._('You need to correct the red marked fields before config could be saved'));
        }
    },
    
    /**
     * @private
     */
    initComponent: function() {
        this.initActions();
        this.items = this.getFormItems();

        Tine.Setup.ConfigManagerGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Setup.ConfigManagerGridPanel.superclass.onRender.call(this, ct, position);
        
        // always the same shit! when form panel is rendered, the form fields itselv are not yet rendered ;-(
        var formData = this.config2form.defer(250, this, [Tine.Setup.registry.get('configData')]);
        
        Tine.Setup.registry.on('replace', this.applyRegistryState, this);
        this.loadMask = new Ext.LoadMask(ct, {msg: this.app.i18n._('Transfering Configuration...')});
    },
    
    /**
     * returns config manager form
     * 
     * @private
     * @return {Array} items
     */
    getFormItems: function() {
        return [/*{
            xtype: 'panel',
            title: this.app.i18n._('Informations'),
            iconCls: 'setup_info',
            html: ''
        },*/ {
            title: this.app.i18n._('Setup Authentication'),
            items: [{
                name: 'setupuser_username',
                fieldLabel: this.app.i18n._('Username'),
                allowBlank: false
            }, {
                name: 'setupuser_password',
                fieldLabel: this.app.i18n._('Password'),
                inputType: 'password',
                allowBlank: false
            }] 
        }, {
            title: this.app.i18n._('Database'),
            id: 'setup-database-group',
            items: [{
                name: 'database_adapter',
                fieldLabel: this.app.i18n._('Adapter'),
                value: 'pdo_mysql',
                disabled: true
            }, {
                name: 'database_host',
                fieldLabel: this.app.i18n._('Hostname'),
                allowBlank: false
            }, {
                name: 'database_dbname',
                fieldLabel: this.app.i18n._('Database'),
                allowBlank: false
            }, {
                name: 'database_username',
                fieldLabel: this.app.i18n._('User'),
                allowBlank: false
            }, {
                name: 'database_password',
                fieldLabel: this.app.i18n._('Password'),
                inputType: 'password'
            }, {
                name: 'database_tableprefix',
                fieldLabel: this.app.i18n._('Prefix')
            }]
        }, {
            title: this.app.i18n._('Logging'),
            id: 'setup-logger-group',
            checkboxToggle:true,
            collapsed: true,
            items: [{
                name: 'logger_filename',
                fieldLabel: this.app.i18n._('Filename')
            }, {
                xtype: 'combo',
                width: 283, // late rendering bug
                listWidth: 300,
                mode: 'local',
                forceSelection: true,
                allowEmpty: false,
                triggerAction: 'all',
                selectOnFocus:true,
                store: [[0, 'Emergency'], [1,'Alert'], [2, 'Critical'], [3, 'Error'], [4, 'Warning'], [5, 'Notice'], [6, 'Informational'], [7, 'Debug']],
                name: 'logger_priority',
                fieldLabel: this.app.i18n._('Priority')

            }]
        }, {
            title: this.app.i18n._('Caching'),
            id: 'setup-caching-group',
            checkboxToggle:true,
            collapsed: true,
            items: [{
                name: 'caching_path',
                fieldLabel: this.app.i18n._('Path')
            }, {
                name: 'caching_lifetime',
                fieldLabel: this.app.i18n._('Lifetime (seconds)'),
                xtype: 'numberfield',
                minValue: 0,
                maxValue: 3600
            }]
        }];
    },
    
    /**
     * transforms form data into a config object
     * 
     * @param  {Object} formData
     * @return {Object} configData
     */
    form2config: function() {
        // getValues only returns RAW HTML content... and we don't want to 
        // define a record here
        var formData = {};
        this.getForm().items.each(function(field) {
            formData[field.name] = field.getValue();
        });
        
        var configData = {};
        var keyParts, keyPart, keyGroup, dataPath;
        for (key in formData) {
            keyParts = key.split('_');
            dataPath = configData;
            
            while (keyPart = keyParts.shift()) {
                if (keyParts.length == 0) {
                    dataPath[keyPart] = formData[key];
                } else {
                    if (!dataPath[keyPart]) {
                        dataPath[keyPart] = {};
                        
                        // is group active?
                        keyGroup = Ext.getCmp('setup-' + keyPart + '-group');
                        if (keyGroup && keyGroup.checkboxToggle) {
                            dataPath[keyPart].active = !keyGroup.collapsed;
                        }
                    }
                
                    dataPath = dataPath[keyPart];
                }
            }
        }
        return configData;
    },
    
    /**
     * loads form with config data
     * 
     * @param  {Object} configData
     */
    config2form: function(configData) {
        var formData = arguments[1] ? arguments[1] : {};
        var currKey  = arguments[2] ? arguments[2] : '';
        
        var keyGroup;
        for (key in configData) {
            if(typeof configData[key] == 'object') {
                this.config2form(configData[key], formData, currKey ? currKey + '_' + key : key);
            } else {
                formData[currKey + '_' + key] = configData[key];
                
                // activate group?
                keyGroup = Ext.getCmp('setup-' + currKey + '-group');
                if (keyGroup && key == 'active' && configData.active) {
                    keyGroup.expand();
                }
            }
        }
        
        // skip transform calls
        if (! currKey) {
            this.getForm().setValues(formData);
            this.applyRegistryState();
        }
    },
    
    /**
     * applies registry state to this cmp
     */
    applyRegistryState: function() {
        this.action_saveConfig.setDisabled(!Tine.Setup.registry.get('configWritable'));
        Ext.getCmp('setup-database-group').setIconClass(Tine.Setup.registry.get('checkDB') ? 'setup_checks_success' : 'setup_checks_fail');
    },
    
    /**
     * @private
     */
    initActions: function() {       
        this.action_saveConfig = new Ext.Action({
            text: this.app.i18n._('Save config'),
            iconCls: 'setup_action_save_config',
            scope: this,
            handler: this.onSaveConfig,
            disabled: true
        });
        
        this.action_downloadConfig = new Ext.Action({
            text: this.app.i18n._('Download config file'),
            iconCls: 'setup_action_download_config',
            disabled: true
        });
        
        this.actionToolbar = new Ext.Toolbar({
            items: [
                this.action_saveConfig,
                this.action_downloadConfig
            ]
        });
    }
}); 