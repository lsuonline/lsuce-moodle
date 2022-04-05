(function (scope, factory) {
    if (typeof define === 'function' && define.amd) {
        define(factory);
        return;
    }

    var instance = factory();

    if (typeof module === 'object') {
        module.exports = instance;
        return;
    }

    scope[instance.name] = instance;
})(this, function () {
    'use strict';

        // // your lib goes here
        // eatshit: function() {
        //     console.log("Hey.........EAT SHIT......signed everybody!");
        // },
        // pissOff: function () {
        //     console.log("Hey.........FOFF YEA!");
        // }

    return function() {
        console.log("Hey, you can now do cool stuff!");
    };
});