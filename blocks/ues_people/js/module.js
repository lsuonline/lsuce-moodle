M.block_ues_people = {};

M.block_ues_people.init = function(Y) {
    Y.all('form[method=POST] input[type=checkbox]').each(function(checkbox) {
        var name = checkbox.get('name');

        var toggle = function(state) {
            return function(elem) {
                return state ? elem.show() : elem.hide();
            };
        };

        checkbox.on('change', function() {
            var checked = checkbox.getDOMNode().checked;
            Y.all('.' + name).each(toggle(checked));
        });
    });
};
