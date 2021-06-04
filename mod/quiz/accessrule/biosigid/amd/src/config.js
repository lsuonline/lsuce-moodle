define([], function () {
    return {
        init: function(biodomain) {
            window.requirejs.config({
                enforceDefine: false,
                paths: {
                    "biosightclient": 'https://' + biodomain + '/js/biosightclient',
                },
                shim: {
                    'biosightclient': {exports: 'biosightclient'},
                }
            });
        }
    };
});
