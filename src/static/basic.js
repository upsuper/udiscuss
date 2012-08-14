var $ = (function () {
    var $ = function (sel, elem) {
        return (elem ? elem : document).querySelector(sel);
    };
    $.all = function (sel, elem) {
        return (elem ? elem : document).querySelectorAll(sel);
    };
    $.create = function (tag) {
        return document.createElement(tag);
    };
    $.escape = function (str) {
        var tagsToReplace = {'&': '&amp;', '<': '&lt;', '>': '&gt;'};
        return str.replace(/&<>/g, function (tag) {
            return tagsToReplace[tag] || tag;
        });
    };
    $.post = function (url, data, opts) {
        if (typeof opts == 'function')
            opts = {onsuccess: opts};
        var headers = opts.headers;
        if (!headers)
            headers = {};
        if (typeof data == 'object') {
            headers['Content-Type'] = 'application/x-www-form-urlencoded';
            var items = [];
            for (var key in data) {
                items.push(encodeURIComponent(key) + '=' +
                    encodeURIComponent(data[key]));
            }
            data = items.join('&');
        }

        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (xhr.readyState != 4)
                return;
            if (xhr.status >= 200 && xhr.status < 300) {
                if (typeof opts.onsuccess != 'function')
                    return;
                var resp = xhr.responseText;
                switch (opts.type) {
                    case 'json':
                        resp = JSON.parse(resp);
                        break;
                }
                opts.onsuccess(resp, xhr.status);
            } else {
                if (typeof opts.onerror == 'function')
                    opts.onerror(xhr.status);
            }
        };
        xhr.open('POST', url);
        for (var name in headers)
            xhr.setRequestHeader(name, headers[name]);
        xhr.send(data);
    };
    return $;
})();

Element.prototype.on = function (evt, listener) {
    return this.addEventListener(evt, listener, false);
};
Element.prototype.empty = function () {
    var p = this;
    p.childNodes.forEach(function (elem) {
        p.removeChild(elem);
    });
};

if (NodeList !== $.all('html').constructor)
    NodeList = $.all('html').constructor;
NodeList.prototype.toArray = function () {
    return Array.prototype.slice.call(this);
};
NodeList.prototype.forEach = function (func) {
    this.toArray().forEach(func, this);
};
