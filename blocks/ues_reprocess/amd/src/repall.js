// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Reprocess All Tool
 * @copyright  Louisiana State University
 * @copyright  The guy who did stuff: David Lowe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax'], function($, Ajax) {
    var t = {

        /**
         * Used to track when the category really changes.
         */
        lastYear: null,
        lastSemester: null,
        lastDepartment: null,
        lastCourse: null,
        /**
         * Initialise the handling.
         */
        init: function() {
            $('select#id_ues_year').on('change', function() {
                $('select#id_ues_courses').empty();

                t.lastYear = $('select#id_ues_year').val();

                t.formChange({
                    'name': t.lastYear,
                    'id': "select#id_ues_year",
                    'meth': "ues_reprocess_get_courses",
                    'args': {
                        'year': $('select#id_ues_year option:selected').text(),
                        'semester': $('select#id_ues_semesters').val(),
                        'department': $('select#id_ues_departments').val(),
                        'update': 'select#id_ues_courses'
                    },
                });
            });

            $('select#id_ues_semesters').on('change', function() {
                $('select#id_ues_courses').empty();
                t.lastSemester = $('select#id_ues_semesters').val();
                t.formChange({
                    'name': t.lastSemester,
                    'id': "select#id_ues_semesters",
                    'meth': "ues_reprocess_get_courses",
                    'args': {
                        'year': $('select#id_ues_year option:selected').text(),
                        'semester': $('select#id_ues_semesters').val(),
                        'department': $('select#id_ues_departments').val(),
                        // The field to update with new data.
                        'update': 'select#id_ues_courses'
                    },
                });
            });

            $('select#id_ues_departments').on('change', function() {
                t.lastDepartment = $('select#id_ues_departments').val();
                $('select#id_ues_courses').empty();
                t.formChange({
                    'name': t.lastDepartment,
                    'id': "select#id_ues_departments",
                    'meth': "ues_reprocess_get_courses",
                    'args': {
                        'year': $('select#id_ues_year option:selected').text(),
                        'semester': $('select#id_ues_semesters').val(),
                        'department': $('select#id_ues_departments').val(),
                        // The field to update with new data.
                        'update': 'select#id_ues_courses'
                    },
                });
            });
            $('select#id_ues_courses').on('change', function() {
                t.lastCourse = $('select#id_ues_courses').val();
                $("input[name='ues_courses_h']").val($('select#id_ues_courses').val());
                /*
                t.formChange({
                    'name': t.lastCourse,
                    'id': 'select#id_ues_courses',
                    'meth': 'ues_reprocess_get_sections',
                    'args': {
                        'year': $('select#id_ues_year option:selected').text(),
                        'semester': $('select#id_ues_semesters').val(),
                        'department': $('select#id_ues_departments').val(),
                        'course': $('select#id_ues_courses').val(),
                        // The field to update with new data.
                        'update': 'select#id_ues_sections'
                    },
                });
                */
            });

            t.year = null;
            t.lastSemester = null;
            t.lastDepartment = null;
            t.lastCourse = null;
        },

        /**
         * Source of data for Ajax element.
         *
         * @param {Object} obj - params used to build the call.
         */
        formChange: function(obj) {
            console.log("Data to send: ", obj);
            if (obj.args.course === null) {
                obj.args.course = '';
            }
            if (t[obj.name] === '') {
                t.updateChoices([]);
            } else {

                Ajax.call([{
                    // get_sharable_question_choices
                    'methodname': obj.meth,
                    'args': obj.args
                }])[0].done(t.updateChoices)

                .fail(function ( jqXHR, textStatus, errorThrown ) {
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                });
            }
        },

        /**
         * Update the contents of the Question select with the results of the AJAX call.
         *
         * @param {Array} response - array of options, each has fields value and label.
         */
        updateChoices: function(response) {
            console.log("What is the returing obj: ", response);
            $('span#repall_estimator_course').text(response.csize);
            $('span#repall_estimator_time').text(
                new Date((response.csize * 4) * 1000).toISOString().slice(11, 19)
            );

            // If there is nothing to update on the form then abort.
            if (response.update == "none") {
                return;
            }
            var select = $(response.update);
            select.empty();
            $(response.data).each(function(index, option) {
                select.append('<option value="' + option.value + '">' + option.label + '</option>');
            });
            // M.util.js_complete('ues_reprocess-get_departments');
        }
    };
    return t;
});