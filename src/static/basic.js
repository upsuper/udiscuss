function $(sel, elem) {
    return (elem ? elem : document).querySelector(sel);
}
function $all(sel, elem) {
    return (elem ? elem : document).querySelectorAll(sel);
}
function $c(tag) {
    return document.createElement(tag);
}

Element.prototype.on = function (evt, listener) {
    return this.addEventListener(evt, listener, false);
};

if (NodeList !== $all('html').constructor)
    NodeList = $all('html').constructor;
NodeList.prototype.toArray = function () {
    return Array.prototype.slice.call(this);
};
NodeList.prototype.forEach = function (func) {
    this.toArray().forEach(func, this);
};
