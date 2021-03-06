var Widget = function() {

    var base = this,
        win = window,
        doc = document;

    // Widget Archive
    base.archive = function(type, id) {

        // Hierarchy
        if (type === 'HIERARCHY') {
            var elem = doc.getElementById(id);
            if (!elem) return;
            var toggle = elem.getElementsByTagName('ul')[0].getElementsByTagName('a'),
                click = function(ref) {
                    var arrow = ['&#9660;', '&#9658;'];
                    ref.onclick = function() {
                        var parent = this.parentNode,
                            s = /\scurrent$/i.test(parent.className) ? ' current' : "";
                        if (this.className === 'toggle close') {
                            this.className = 'toggle open';
                            this.innerHTML = arrow[0];
                            parent.className = 'open' + s;
                        } else {
                            this.className = 'toggle close';
                            this.innerHTML = arrow[1];
                            parent.className = 'close' + s;
                        }
                        return false;
                    };
                };
            if (!toggle) return;
            for (var i = 0, toggles = toggle.length; i < toggles; ++i) {
                if (/(^|\s)toggle(\s|$)/.test(toggle[i].className)) click(toggle[i]);
            }
        }

        // Dropdown
        if (type === 'DROPDOWN') {
            elem = doc.getElementById(id);
            if (!elem) return;
            var select = elem.getElementsByTagName('select')[0];
            if (!select) return;
            select.onchange = function() {
                win.location.href = this.value;
            };
        }

    };

    // Widget Tag
    base.tag = function(type, id) {

        // Dropdown
        if (type === 'DROPDOWN') {
            elem = doc.getElementById(id);
            if (!elem) return;
            var select = elem.getElementsByTagName('select')[0];
            if (!select) return;
            select.onchange = function() {
                win.location.href = this.value;
            };
        }

    };

};


/**
 * FIRE !!!
 * --------
 */

(function(d) {
    var elem = d.getElementsByTagName('div'),
        widget = new Widget();
    for (var i = 0, len = elem.length; i < len; ++i) {
        var e_class = elem[i].className,
            e_id = elem[i].id;
        if (/(^|\s)widget-archive widget-archive-hierarchy(\s|$)/.test(e_class)) {
            widget.archive('HIERARCHY', e_id);
        }
        if (/(^|\s)widget-archive widget-archive-dropdown(\s|$)/.test(e_class)) {
            widget.archive('DROPDOWN', e_id);
        }
        if (/(^|\s)widget-tag widget-tag-dropdown(\s|$)/.test(e_class)) {
            widget.tag('DROPDOWN', e_id);
        }
    }
})(document);