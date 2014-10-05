function autouserlookup(Y, listofusers, nameofdiv){
    YUI().use('autocomplete', 'autocomplete-filters', 'autocomplete-highlighters', function (Y) {

      Y.one(nameofdiv).plug(Y.Plugin.AutoComplete, {
        resultFilters    : 'startsWith',
        resultHighlighter: 'startsWith',
        maxResults       : 'maxResults',
        minQueryLength   : '3',
        source           : listofusers
      });
    });
}