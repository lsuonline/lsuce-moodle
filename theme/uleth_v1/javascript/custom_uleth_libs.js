/*global $:false */

/**
 * ************************************************************************
 * *                     Tools for the UofL                              **
 * ************************************************************************
 * @package     Theme
 * @subpackage  University of Lethbridge 
 * @name        Custom Uleth JS
 * @author      David Lowe
 * ************************************************************************
 * ********************************************************************** */

requirejs.config({
    paths: {
        "pnotify": '/theme/uleth/javascript/pnotify.custom',
    },
    shim: {
        'pnotify' : {deps: ['jquery']}
    }
});