//
// This is the main app for the reslib module
//
function ciniki_reslib_main() {
    //
    // The panel to list the section
    //
    this.menu = new M.panel('Resource Library', 'ciniki_reslib_main', 'menu', 'mc', 'xlarge narrowaside', 'sectioned', 'ciniki.reslib.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.section_id = 0;
    this.menu.category_id = 0;
    this.menu.subcategory_id = 0;
    this.menu.sections = {
        'sections':{'label':'Section', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'cellClasses':['multiline'],
            'noData':'No section',
            'editFn':function(s,i,d) {
                if( d != null ) {
                    return 'M.ciniki_reslib_main.section.open(\'M.ciniki_reslib_main.menu.open();\',\'' + d.id + '\');';
                }
                return '';
                },
            'seqDrop':function(e,from,to){
                M.api.getJSONCb('ciniki.reslib.sectionUpdate', {'tnid':M.curTenantID, 
                    'section_id':M.ciniki_reslib_main.menu.data.sections[from].id,
                    'sequence':M.ciniki_reslib_main.menu.data.sections[to].sequence,
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_reslib_main.menu.open();
                    });
                },
            'menu':{
                'add':{
                    'label':'Add Section',
                    'fn':'M.ciniki_reslib_main.section.open(\'M.ciniki_reslib_main.menu.open();\',0,null);'
                    },
                },
            },
        'categories':{'label':'Category', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_reslib_main.menu.section_id > 0 ? 'yes' : 'no';},
            'cellClasses':['multiline'],
            'noData':'No category',
            'editFn':function(s,i,d) {
//                if( d != null && i > 0 ) {
                    return 'M.ciniki_reslib_main.category.open(\'M.ciniki_reslib_main.menu.open();\',\'' + d.id + '\');';
//                }
//                return '';
                },
            'seqDrop':function(e,from,to){
                if( from == 0 || to == 0 ) {
                    return true;
                }
                M.api.getJSONCb('ciniki.reslib.categoryUpdate', {'tnid':M.curTenantID, 
                    'category_id':M.ciniki_reslib_main.menu.data.categories[from].id,
                    'sequence':M.ciniki_reslib_main.menu.data.categories[to].sequence,
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_reslib_main.menu.open();
                    });
                },
            'menu':{
                'add':{
                    'label':'Add Category',
                    'fn':'M.ciniki_reslib_main.category.open(\'M.ciniki_reslib_main.menu.open();\',0,M.ciniki_reslib_main.menu.section_id,null);'
                    },
                },
            },
        'subcategories':{'label':'Subcategory', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'visible':function() { return M.ciniki_reslib_main.menu.category_id > 0 ? 'yes' : 'no';},
            'cellClasses':['multiline'],
            'noData':'No subcategory',
            'editFn':function(s,i,d) {
                if( d != null && i > 0 ) {
                    return 'M.ciniki_reslib_main.subcategory.open(\'M.ciniki_reslib_main.menu.open();\',\'' + d.id + '\');';
                }
                return '';
                },
            'seqDrop':function(e,from,to){
                if( from == 0 || to == 0 ) {
                    return true;
                }
                M.api.getJSONCb('ciniki.reslib.subcategoryUpdate', {'tnid':M.curTenantID, 
                    'subcategory_id':M.ciniki_reslib_main.menu.data.subcategories[from].id,
                    'sequence':M.ciniki_reslib_main.menu.data.subcategories[to].sequence,
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_reslib_main.menu.open();
                    });
                },
            'menu':{
                'add':{
                    'label':'Add Subcategory',
                    'fn':'M.ciniki_reslib_main.subcategory.open(\'M.ciniki_reslib_main.menu.open();\',0,M.ciniki_reslib_main.menu.category_id,null);'
                    },
                },
            },
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':2,
            'cellClasses':[''],
            'hint':'Search items',
            'noData':'No items found',
            'headerValues':['Name', 'Resource'],
            'cellClasses':['', ''],
            },
        'items':{'label':'Items', 'type':'simplegrid', 'num_cols':2,
            'headerValues':['Name', 'Resource'],
            'cellClasses':['', ''],
            'noData':'No Items',
            'seqDrop':function(e,from,to) {
                M.api.getJSONCb('ciniki.reslib.itemUpdate', {'tnid':M.curTenantID, 
                    'item_id':M.ciniki_reslib_main.menu.data.items[from].id,
                    'sequence':M.ciniki_reslib_main.menu.data.items[to].sequence,
                    }, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_reslib_main.menu.open();
                    });
                },
            'menu':{
                'visible':function() { return M.ciniki_reslib_main.menu.subcategory_id > 0 ? 'yes' : 'no'; },
                'add':{
                    'label':'Add Item',
                    'fn':'M.ciniki_reslib_main.item.open(\'M.ciniki_reslib_main.menu.open();\',0,M.ciniki_reslib_main.menu.subcategory_id,null);',
                    },
                'resort':{
                    'label':'Sort Alphabetical',
                    'fn':'M.ciniki_reslib_main.menu.resortItems(M.ciniki_reslib_main.menu.subcategory_id);',
                    },
                },
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.reslib.itemSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_reslib_main.menu.liveSearchShow('search',null,M.gE(M.ciniki_reslib_main.menu.panelUID + '_' + s), rsp.items);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return this.cellValue(s, i, j, d);
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return this.rowFn(s, i, d);
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'sections' ) {
            switch(j) {
                case 0: return M.multiline(d.name, d.customer_perms);
            }
        }
        if( s == 'categories' ) {
            switch(j) {
                case 0: return M.multiline(d.name, d.customer_perms);
            }
        }
        if( s == 'subcategories' ) {
            switch(j) {
                case 0: return M.multiline(d.name, d.customer_perms);
            }
        }
        if( s == 'items' || s == 'search' ) {
/*            if( j == 0 ) {
                if( d.thumbnail_image_id > 0 ) {
                    if( d.image != null && d.image != '' ) {
                        return '<img width="30px" height="30px" src=\'' + d.image + '\' />'; 
                    } else {
                        return '<img width="30px" height="30px" src=\'' + M.api.getBinaryURL('ciniki.images.get', {'tnid':M.curTenantID, 'image_id':d.image_id, 'version':'thumbnail', 'maxwidth':'30'}) + '\' />'; 
                    }
                } else {
                    return '<img width="25px" height="25px" src=\'/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg\' />';
                }
            } */
            switch(j) {
                case 0: return d.name;
                case 1: return d.resource;
                }
        }
    }
    this.menu.rowClass = function(s, i, d) {
        if( s == 'sections' && this.section_id == d.id ) {
            return 'highlight';
        }
        if( s == 'categories' && this.category_id == d.id ) {
            return 'highlight';
        }
        if( s == 'subcategories' && this.subcategory_id == d.id ) {
            return 'highlight';
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'sections' ) {
            return 'M.ciniki_reslib_main.menu.switchSection(\'' + d.id + '\');';
        }
        if( s == 'categories' ) {
            return 'M.ciniki_reslib_main.menu.switchCategory(\'' + d.id + '\');';
        }
        if( s == 'subcategories' ) {
            return 'M.ciniki_reslib_main.menu.switchSubcategory(\'' + d.id + '\');';
        }
        if( s == 'items' || s == 'search' ) {
            return 'M.ciniki_reslib_main.item.open(\'M.ciniki_reslib_main.menu.open();\',\'' + d.id + '\',0,null);';
        }
    }
    this.menu.switchSection = function(s) {
        this.section_id = s;
        this.category_id = 0;
        this.subcategory_id = 0;
        this.open();
    }
    this.menu.switchCategory = function(c) {
        this.category_id = c;
        this.subcategory_id = 0;
        this.open();
    }
    this.menu.switchSubcategory = function(s) {
        this.subcategory_id = s;
        this.open();
    }
    this.menu.resortItems = function(s) {
        this.open(null,'yes');
    }
    this.menu.open = function(cb,resort) {
        var args = {'tnid':M.curTenantID};
        if( this.section_id > 0 ) {
            args.section_id = this.section_id;
        }
        if( this.category_id > 0 ) {
            args.category_id = this.category_id;
        }
        if( this.subcategory_id > 0 ) {
            args.subcategory_id = this.subcategory_id;
        }
        if( resort != null && resort == 'yes' ) {
            args.action = 'resort';
        }
        M.api.getJSONCb('ciniki.reslib.items', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_reslib_main.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            if( p.subcategory_id > 0 ) {
                p.sections.items.seqDrop = function(e, from, to) {
                    M.api.getJSONCb('ciniki.reslib.itemUpdate', {'tnid':M.curTenantID, 
                        'item_id':M.ciniki_reslib_main.menu.data.items[from].id,
                        'sequence':M.ciniki_reslib_main.menu.data.items[to].sequence,
                        }, function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            M.ciniki_reslib_main.menu.open();
                        });
                };
            } else {
                delete p.sections.items.seqDrop;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to edit Section
    //
    this.section = new M.panel('Section', 'ciniki_reslib_main', 'section', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.reslib.main.section');
    this.section.data = null;
    this.section.section_id = 0;
    this.section.nplist = [];
    this.section.sections = {
        '_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_reslib_main.section.setFieldValue('image_id', iid);
                    return true;
                    },
                'deleteImage':function(fid) {
                    M.ciniki_reslib_main.section.setFieldValue(fid,0);
                    return true;
                 },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'Section', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
//            'flags':{'label':'Options', 'type':'text'},
//            'sequence':{'label':'Order', 'type':'text'},
            'restrictions':{'label':'Access', 'type':'toggle', 'default':'no', 
                'visible':function() { return M.modFlagSet('ciniki.customers', 0x1000); },
                'onchange':'M.ciniki_reslib_main.section.updatePerms();',
                'toggles':{ 
                    'no':'No Restrictions',
                    'limit':'Limit Access',
                }},
            }},
        '_customer_perms':{'label':'Customer Permissions', 'aside':'yes', 
            'visible':'hidden',
            'fields':{
                'customer_perms':{'label':'', 'hidelabel':'yes', 'type':'tags', 'add':'no', 'tags':[]},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_reslib_main.section.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_reslib_main.section.section_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_reslib_main.section.remove();'},
            }},
        };
    this.section.fieldValue = function(s, i, d) { return this.data[i]; }
    this.section.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.reslib.sectionHistory', 'args':{'tnid':M.curTenantID, 'section_id':this.section_id, 'field':i}};
    }
    this.section.updatePerms = function() {
        var v = this.formValue('restrictions');
        if( M.modFlagOn('ciniki.customers', 0x1000) && v == 'limit' ) {
            this.sections._customer_perms.visible = 'yes';
        } else {
            this.sections._customer_perms.visible = 'hidden';
        }
        this.showHideSection('_customer_perms');
    }
    this.section.open = function(cb, sid, list) {
        if( sid != null ) { this.section_id = sid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.reslib.sectionGet', {'tnid':M.curTenantID, 'section_id':this.section_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_reslib_main.section;
            p.data = rsp.section;
            p.sections._customer_perms.fields.customer_perms.tags = rsp['customers-permission-tags'];
            p.refresh();
            p.show(cb);
            p.updatePerms();
        });
    }
    this.section.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_reslib_main.section.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.section_id > 0 ) {
            // Check if restrictions are enabled, then if not make sure customer_perms is blank
            var v = this.formValue('restrictions');
            if( v == 'no' ) {
                // Skip adding customer perms if no limit
                this.sections._customer_perms.fields.customer_perms.active = 'no';
                var c = this.serializeForm('no');
                this.sections._customer_perms.fields.customer_perms.active = 'yes';
                if( this.data['customer_perms'] != '' ) {
                    // no limit force to blank
                    c = '&customer_perms=';
                }
            } else {
                var c = this.serializeForm('no');
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.reslib.sectionUpdate', {'tnid':M.curTenantID, 'section_id':this.section_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.reslib.sectionAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_reslib_main.section.section_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.section.remove = function() {
        M.confirm('Are you sure you want to remove section?', null, function(rsp) {
            M.api.getJSONCb('ciniki.reslib.sectionDelete', {'tnid':M.curTenantID, 'section_id':M.ciniki_reslib_main.section.section_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_reslib_main.section.close();
            });
        });
    }
    this.section.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.section_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_reslib_main.section.save(\'M.ciniki_reslib_main.section.open(null,' + this.nplist[this.nplist.indexOf('' + this.section_id) + 1] + ');\');';
        }
        return null;
    }
    this.section.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.section_id) > 0 ) {
            return 'M.ciniki_reslib_main.section.save(\'M.ciniki_reslib_main.section.open(null,' + this.nplist[this.nplist.indexOf('' + this.section_id) - 1] + ');\');';
        }
        return null;
    }
    this.section.addButton('save', 'Save', 'M.ciniki_reslib_main.section.save();');
    this.section.addClose('Cancel');
    this.section.addButton('next', 'Next');
    this.section.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Category
    //
    this.category = new M.panel('Category', 'ciniki_reslib_main', 'category', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.reslib.main.category');
    this.category.data = null;
    this.category.category_id = 0;
    this.category.nplist = [];
    this.category.sections = {
        '_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no', 'delete':'yes',
                'addDropImage':function(iid) {
                    M.ciniki_reslib_main.category.setFieldValue('image_id', iid);
                    return true;
                    },
                'deleteImage':function(fid) {
                    M.ciniki_reslib_main.category.setFieldValue(fid,0);
                    return true;
                 },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'', 'aside':'yes', 'fields':{
            'section_id':{'label':'Section', 'type':'select', 'options':[], 'complex_options':{'value':'id', 'name':'name'}},
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
//            'flags':{'label':'Options', 'type':'text'},
//            'sequence':{'label':'Order', 'type':'text'},
            'restrictions':{'label':'Access', 'type':'toggle', 'default':'no', 
                'visible':function() { return M.modFlagSet('ciniki.customers', 0x1000); },
                'onchange':'M.ciniki_reslib_main.category.updatePerms();',
                'toggles':{ 
                    'no':'No Restrictions',
                    'limit':'Limit Access',
                }},
            }},
        '_customer_perms':{'label':'Customer Permissions', 'aside':'yes', 
            'visible':'hidden',
            'fields':{
                'customer_perms':{'label':'', 'hidelabel':'yes', 'type':'tags', 'add':'no', 'tags':[]},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_reslib_main.category.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_reslib_main.category.category_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_reslib_main.category.remove();'},
            }},
        };
    this.category.fieldValue = function(s, i, d) { return this.data[i]; }
    this.category.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.reslib.categoryHistory', 'args':{'tnid':M.curTenantID, 'category_id':this.category_id, 'field':i}};
    }
    this.category.updatePerms = function() {
        var v = this.formValue('restrictions');
        if( M.modFlagOn('ciniki.customers', 0x1000) && v == 'limit' ) {
            this.sections._customer_perms.visible = 'yes';
        } else {
            this.sections._customer_perms.visible = 'hidden';
        }
        this.showHideSection('_customer_perms');
    }
    this.category.open = function(cb, cid, sid, list) {
        if( cid != null ) { this.category_id = cid; }
        if( sid != null ) { this.section_id = sid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.reslib.categoryGet', {'tnid':M.curTenantID, 'category_id':this.category_id, 'section_id':this.section_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_reslib_main.category;
            p.data = rsp.category;
            p.sections.general.fields.section_id.options = rsp.sections;
            p.sections._customer_perms.fields.customer_perms.tags = rsp['customers-permission-tags'];
            p.refresh();
            p.show(cb);
            p.updatePerms();
        });
    }
    this.category.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_reslib_main.category.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.category_id > 0 ) {
            // Check if restrictions are enabled, then if not make sure customer_perms is blank
            var v = this.formValue('restrictions');
            if( v == 'no' ) {
                // Skip adding customer perms if no limit
                this.sections._customer_perms.fields.customer_perms.active = 'no';
                var c = this.serializeForm('no');
                this.sections._customer_perms.fields.customer_perms.active = 'yes';
                if( this.data['customer_perms'] != '' ) {
                    // no limit force to blank
                    c = '&customer_perms=';
                }
            } else {
                var c = this.serializeForm('no');
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.reslib.categoryUpdate', {'tnid':M.curTenantID, 'category_id':this.category_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.reslib.categoryAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_reslib_main.category.category_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.category.remove = function() {
        M.confirm('Are you sure you want to remove category?', null, function(rsp) {
            M.api.getJSONCb('ciniki.reslib.categoryDelete', {'tnid':M.curTenantID, 'category_id':M.ciniki_reslib_main.category.category_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_reslib_main.category.close();
            });
        });
    }
    this.category.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.category_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_reslib_main.category.save(\'M.ciniki_reslib_main.category.open(null,' + this.nplist[this.nplist.indexOf('' + this.category_id) + 1] + ');\');';
        }
        return null;
    }
    this.category.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.category_id) > 0 ) {
            return 'M.ciniki_reslib_main.category.save(\'M.ciniki_reslib_main.category.open(null,' + this.nplist[this.nplist.indexOf('' + this.category_id) - 1] + ');\');';
        }
        return null;
    }
    this.category.addButton('save', 'Save', 'M.ciniki_reslib_main.category.save();');
    this.category.addClose('Cancel');
    this.category.addButton('next', 'Next');
    this.category.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Subcategory
    //
    this.subcategory = new M.panel('Subcategory', 'ciniki_reslib_main', 'subcategory', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.reslib.main.subcategory');
    this.subcategory.data = null;
    this.subcategory.subcategory_id = 0;
    this.subcategory.nplist = [];
    this.subcategory.sections = {
        '_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_reslib_main.subcategory.setFieldValue('image_id', iid);
                    return true;
                    },
                'deleteImage':function(fid) {
                    M.ciniki_reslib_main.subcategory.setFieldValue(fid,0);
                    return true;
                 },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'', 'aside':'yes', 'fields':{
            'category_id':{'label':'Category', 'type':'select', 'options':[], 'complex_options':{'value':'id', 'name':'name'}},
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
//            'flags':{'label':'Options', 'type':'text'},
//            'sequence':{'label':'Order', 'type':'text'},
            'restrictions':{'label':'Access', 'type':'toggle', 'default':'no', 
                'visible':function() { return M.modFlagSet('ciniki.customers', 0x1000); },
                'onchange':'M.ciniki_reslib_main.subcategory.updatePerms();',
                'toggles':{ 
                    'no':'No Restrictions',
                    'limit':'Limit Access',
                }},
            }},
        '_customer_perms':{'label':'Customer Permissions', 'aside':'yes', 
            'visible':'hidden',
            'fields':{
                'customer_perms':{'label':'', 'hidelabel':'yes', 'type':'tags', 'add':'no', 'tags':[]},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_reslib_main.subcategory.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_reslib_main.subcategory.subcategory_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_reslib_main.subcategory.remove();'},
            }},
        };
    this.subcategory.fieldValue = function(s, i, d) { return this.data[i]; }
    this.subcategory.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.reslib.subcategoryHistory', 'args':{'tnid':M.curTenantID, 'subcategory_id':this.subcategory_id, 'field':i}};
    }
    this.subcategory.updatePerms = function() {
        var v = this.formValue('restrictions');
        if( M.modFlagOn('ciniki.customers', 0x1000) && v == 'limit' ) {
            this.sections._customer_perms.visible = 'yes';
        } else {
            this.sections._customer_perms.visible = 'hidden';
        }
        this.showHideSection('_customer_perms');
    }
    this.subcategory.open = function(cb, sid, cid, list) {
        if( sid != null ) { this.subcategory_id = sid; }
        if( cid != null ) { this.category_id = cid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.reslib.subcategoryGet', {'tnid':M.curTenantID, 'subcategory_id':this.subcategory_id, 'category_id':this.category_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_reslib_main.subcategory;
            p.data = rsp.subcategory;
            p.sections.general.fields.category_id.options = rsp.categories;
            p.sections._customer_perms.fields.customer_perms.tags = rsp['customers-permission-tags'];
            p.refresh();
            p.show(cb);
            p.updatePerms();
        });
    }
    this.subcategory.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_reslib_main.subcategory.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.subcategory_id > 0 ) {
            // Check if restrictions are enabled, then if not make sure customer_perms is blank
            var v = this.formValue('restrictions');
            if( v == 'no' ) {
                // Skip adding customer perms if no limit
                this.sections._customer_perms.fields.customer_perms.active = 'no';
                var c = this.serializeForm('no');
                this.sections._customer_perms.fields.customer_perms.active = 'yes';
                if( this.data['customer_perms'] != '' ) {
                    // no limit force to blank
                    c = '&customer_perms=';
                }
            } else {
                var c = this.serializeForm('no');
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.reslib.subcategoryUpdate', {'tnid':M.curTenantID, 'subcategory_id':this.subcategory_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.reslib.subcategoryAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_reslib_main.subcategory.subcategory_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.subcategory.remove = function() {
        M.confirm('Are you sure you want to remove subcategory?', null, function(rsp) {
            M.api.getJSONCb('ciniki.reslib.subcategoryDelete', {'tnid':M.curTenantID, 'subcategory_id':M.ciniki_reslib_main.subcategory.subcategory_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_reslib_main.subcategory.close();
            });
        });
    }
    this.subcategory.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.subcategory_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_reslib_main.subcategory.save(\'M.ciniki_reslib_main.subcategory.open(null,' + this.nplist[this.nplist.indexOf('' + this.subcategory_id) + 1] + ');\');';
        }
        return null;
    }
    this.subcategory.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.subcategory_id) > 0 ) {
            return 'M.ciniki_reslib_main.subcategory.save(\'M.ciniki_reslib_main.subcategory.open(null,' + this.nplist[this.nplist.indexOf('' + this.subcategory_id) - 1] + ');\');';
        }
        return null;
    }
    this.subcategory.addButton('save', 'Save', 'M.ciniki_reslib_main.subcategory.save();');
    this.subcategory.addClose('Cancel');
    this.subcategory.addButton('next', 'Next');
    this.subcategory.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Item
    //
    this.item = new M.panel('Item', 'ciniki_reslib_main', 'item', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.reslib.main.item');
    this.item.data = null;
    this.item.item_id = 0;
    this.item.nplist = [];
    this.item.sections = {
        '_image_id':{'label':'Thumbnail', 'type':'imageform', 'aside':'yes', 'fields':{
            'thumbnail_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_reslib_main.subcategory.setFieldValue('thumbnail_image_id', iid);
                    return true;
                    },
                'deleteImage':function(fid) {
                    M.ciniki_reslib_main.subcategory.setFieldValue(fid,0);
                    return true;
                 },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'Item', 'aside':'no', 'fields':{
            'subcategory_id':{'label':'Subcategory', 'type':'select', 'options':[], 'complex_options':{'value':'id', 'name':'name'}},
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'restype':{'label':'Item Type', 'type':'toggle', 'default':'10', 
                // restype can't be changed
                'visible':function() { return M.ciniki_reslib_main.item.item_id > 0 ? 'no' : 'yes'; },
                'onchange':'M.ciniki_reslib_main.item.updateForm',
                'toggles':{
                    '10':'File',
                    '30':'Video URL',
                }},
            'url':{'label':'Url', 'type':'text', 'visible':'no'},
            'org_filename':{'label':'Filename', 'type':'file', 'visible':'yes',
                'downloadFn':'M.ciniki_reslib_main.item.downloadFile();',
                },
//            'flags':{'label':'Options', 'type':'text'},
//            'sequence':{'label':'Order', 'type':'text'},
            'synopsis':{'label':'Synopsis', 'type':'textarea', 'size':'small'},
            'additional_keywords':{'label':"Additional Keywords", 'type':'textarea', 'size':'small'},
            }},
//        '_synopsis':{'label':'Synopsis', 'fields':{
//            }},
        '_description':{'label':'Description', 'visible':'hidden', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_reslib_main.item.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_reslib_main.item.item_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_reslib_main.item.remove();'},
            }},
        };
    this.item.fieldValue = function(s, i, d) { return this.data[i]; }
    this.item.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.reslib.itemHistory', 'args':{'tnid':M.curTenantID, 'item_id':this.item_id, 'field':i}};
    }
    this.item.downloadFile = function() {
        M.api.openFile('ciniki.reslib.itemGet',{'tnid':M.curTenantID, 'item_id':this.item_id, 'download':'yes'});
    }
    this.item.updateForm = function() {
        var t = this.formValue('restype');
        if( t == 30 ) {
            this.sections.general.fields.url.visible = 'yes';
            this.sections.general.fields.org_filename.visible = 'no';
            this.sections._description.visible = 'yes';
        } else {
            this.sections.general.fields.url.visible = 'no';
            this.sections.general.fields.org_filename.visible = 'yes';
            this.sections._description.visible = 'hidden';
        }
        this.showHideSection('_description');
        this.refreshFormField('general', 'url');
        this.refreshFormField('general', 'org_filename');
    }
    this.item.open = function(cb, iid, sid, list) {
        if( iid != null ) { this.item_id = iid; }
        if( sid != null ) { this.subcategory_id = sid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.reslib.itemGet', {'tnid':M.curTenantID, 'item_id':this.item_id, 'subcategory_id':sid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_reslib_main.item;
            p.data = rsp.item;
            p.sections.general.fields.subcategory_id.options = rsp.subcategories;
            p.refresh();
            p.show(cb);
            p.updateForm();
        });
    }
    this.item.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_reslib_main.item.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.item_id > 0 ) {
            var c = this.serializeFormData('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.reslib.itemUpdate', {'tnid':M.curTenantID, 'item_id':this.item_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeFormData('yes');
            M.api.postJSONFormData('ciniki.reslib.itemAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_reslib_main.item.item_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.item.remove = function() {
        M.confirm('Are you sure you want to remove item?', null, function(rsp) {
            M.api.getJSONCb('ciniki.reslib.itemDelete', {'tnid':M.curTenantID, 'item_id':M.ciniki_reslib_main.item.item_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_reslib_main.item.close();
            });
        });
    }
    this.item.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.item_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_reslib_main.item.save(\'M.ciniki_reslib_main.item.open(null,' + this.nplist[this.nplist.indexOf('' + this.item_id) + 1] + ');\');';
        }
        return null;
    }
    this.item.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.item_id) > 0 ) {
            return 'M.ciniki_reslib_main.item.save(\'M.ciniki_reslib_main.item.open(null,' + this.nplist[this.nplist.indexOf('' + this.item_id) - 1] + ');\');';
        }
        return null;
    }
    this.item.addButton('save', 'Save', 'M.ciniki_reslib_main.item.save();');
    this.item.addClose('Cancel');
    this.item.addButton('next', 'Next');
    this.item.addLeftButton('prev', 'Prev');


    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'ciniki_reslib_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }
        
        //
        // Create the app container
        //
        if( this.curTenantID == null || this.curTenantID != M.curTenantID ) {
            this.tenantInit();
            this.curTenantID = M.curTenantID;
        }
        
        this.menu.open(cb);
    }

    //
    // Called when switching tenants
    //
    this.tenantInit = function() {
    }
}
