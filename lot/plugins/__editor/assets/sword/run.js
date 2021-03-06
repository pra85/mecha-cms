/**
 * Run the MTE Plugin
 * ------------------
 *
 * Apply MTE to all `<textarea>` elements ...
 *
 */

(function(w, d, base) {
    if (typeof MTE === "undefined") return;
    base.add('on_ajax_success', function(data) {
        base.fire('on_preview_complete', data);
    });
    base.add('on_ajax_error', function(data) {
        base.fire('on_preview_failure', data);
    });
    var area = d.getElementsByTagName('textarea'),
        speak = base.languages.MTE;
    if (!area || !area.length) return;
    var c_na = "", c_nu = 0;
    base.composers = [];
    base.editors = [];
    for (var i = 0, len = area.length; i < len; ++i) {
        var name = area[i].name,
            className = area[i].className,
            is_target = /(^|\s)(MTE|code)(\s|$)/.test(className) && !/(^|\s)MTE-ignore(\s|$)/.test(className),
            is_composer = is_target && /(^|\s)MTE(\s|$)/.test(className),
            hook, config, prefix;
        if (c_na !== name) {
            c_na = name;
            c_nu = 0;
        }
        // Replace `foo[]` with `foo_0`
        // Replace `foo[bar]` with `foo_bar`
        hook = name.replace(/\[\]/g, '_' + c_nu).replace(/\[(.*?)\]/g, '_$1');
        config = area[i].getAttribute('data-MTE-config') || '{}';
        config = typeof JSON.parse === "function" ? JSON.parse(config) : {};
        prefix = is_composer ? 'composer' : 'editor';
        if (is_target) {
            base[is_composer ? 'composers' : 'editors'].push(name);
        }
        base.fire('on_' + prefix + '_begin', {
            'index': i,
            'info': {
                'segment': base.segment,
                'name': name
            }
        });
        base[prefix + '_' + hook] = is_target ? new MTE(area[i], base.task.extend({
            tabSize: TAB || '    ',
            toolbar: false,
            shortcut: false,
            areaClass: 'editor-area',
            toolbarClass: 'editor-toolbar cf',
            toolbarIconClass: 'fa fa-%s',
            toolbarButtonClass: 'editor-toolbar-button editor-toolbar-button-%s',
            toolbarSeparatorClass: 'editor-toolbar-separator',
            dropClass: 'custom-drop custom-drop-%s cf',
            modalClass: 'custom-modal custom-modal-%s cf',
            modalHeaderClass: 'custom-modal-header custom-modal-%s-header cf',
            modalContentClass: 'custom-modal-content custom-modal-%s-content cf',
            modalFooterClass: 'custom-modal-action custom-modal-%s-action cf',
            modalOverlayClass: 'custom-modal-overlay custom-modal-%s-overlay cf',
            emptyElementSuffix: ES || '>',
            buttons: speak.buttons,
            prompts: speak.prompts,
            placeholders: speak.placeholders,
            update: function(e, editor, id) {
                base.fire('on_' + prefix + '_event_update', {
                    'event': e,
                    'editor': editor,
                    'id': id || null,
                    'index': i,
                    'info': {
                        'segment': base.segment,
                        'name': name
                    }
                });
            },
            click: function(e, editor, id) {
                base.fire('on_' + prefix + '_event_click', {
                    'event': e,
                    'editor': editor,
                    'id': id,
                    'index': i,
                    'info': {
                        'segment': base.segment,
                        'name': name
                    }
                });
            },
            keydown: function(e, editor) {
                base.fire('on_' + prefix + '_event_keydown', {
                    'event': e,
                    'editor': editor,
                    'index': i,
                    'info': {
                        'segment': base.segment,
                        'name': name
                    }
                });
            },
            ready: function(editor) {
                base.fire('on_' + prefix + '_event_ready', {
                    'editor': editor,
                    'index': i,
                    'info': {
                        'segment': base.segment,
                        'name': name
                    }
                });
            },
            copy: function(s) {
                base.fire('on_' + prefix + '_event_copy', {
                    'selection': s,
                    'index': i,
                    'info': {
                        'segment': base.segment,
                        'name': name
                    }
                });
            },
            cut: function(s) {
                base.fire('on_' + prefix + '_event_cut', {
                    'selection': s,
                    'index': i,
                    'info': {
                        'segment': base.segment,
                        'name': name
                    }
                });
            },
            paste: function(s) {
                base.fire('on_' + prefix + '_event_paste', {
                    'selection': s,
                    'index': i,
                    'info': {
                        'segment': base.segment,
                        'name': name
                    }
                });
            }
        }, config)) : {};
        if (typeof base[prefix] === "undefined" && typeof base[prefix + '_' + hook].grip !== "undefined" || /(^|\s)MTE-main(\s|$)/.test(className)) {
            base[prefix] = base[prefix + '_' + hook];
        }
        base.fire('on_' + prefix + '_end', {
            'index': i,
            'info': {
                'segment': base.segment,
                'name': name
            }
        });
        c_nu++;
    }
})(window, document, DASHBOARD);