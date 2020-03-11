/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   theme_snap
 * @author    Oscar Nadjar oscar.nadjar@blackboard.com
 * @copyright Copyright (c) 2019 Blackboard Inc. (http://www.blackboard.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * JS code to assign attributes and expected behavior for elements in the Dom regarding accessibility.
 */
define(['jquery', 'core/str', 'core/event'],
    function($, str, Event) {
        return {
            init: function() {

                /**
                 * Module to get the strings from Snap to add the aria-label attribute to new accessibility features.
                 */
                str.get_strings([
                    {key : 'accesforumstringdis', component : 'theme_snap'},
                    {key : 'accesforumstringmov', component : 'theme_snap'},
                    {key : 'calendar', component : 'calendar'}
                ]).done(function(stringsjs) {
                    $("i.fa-calendar").parent().attr("aria-label", stringsjs[2]);
                    $("input[name='TimeEventSelector[calendar]']").attr('aria-label', stringsjs[2]);
                    if ($("#page-mod-forum-discuss")) {
                        $(".displaymode form select.custom-select").attr("aria-label", stringsjs[0]);
                        $(".movediscussion select.urlselect").attr("aria-label", stringsjs[1]);
                    }
                });

                $(document).ready(function() {
                    // Add necessary attributes to needed DOM elements to new accessibility features.
                    $("#page-mod-data-edit input[id*='url']").attr("type", "url").attr("autocomplete", "url");
                    $("#moodle-blocks aside#block-region-side-pre a.sr-only.sr-only-focusable").attr("tabindex", "-1");

                    // Focus first invalid input after a submit is done.
                    $('.mform').submit(function() {
                        $('input.form-control.is-invalid:first').focus();
                    });

                    // Retrieve value from the input buttons from add/remove users in a group inside a course.
                    var addtext = $('.groupmanagementtable #buttonscell p.arrow_button input[name="add"]').attr('value');
                    var removetext = $(".groupmanagementtable #buttonscell p.arrow_button input[name='remove']").attr('value');

                    // Snap tab panels.
                    new Tabpanel("snap-pm-accessible-tab");
                    new Tabpanel("modchooser-accessible-tab");

                    /**
                     * Store the references outside the event handler.
                     * Window reload to change the inputs value for Add and Remove buttons when adding new
                     * members to a group.
                     */
                    var $window = $(window);

                    function checkWidth() {
                        var windowsize = $window.width();
                        if (windowsize < 1220) {
                            $(".groupmanagementtable #buttonscell p.arrow_button input[name='add']").attr("value", "+");
                            $(".groupmanagementtable #buttonscell p.arrow_button input[name='remove']").attr("value", "-");
                        } else if (windowsize > 1220) {
                            $(".groupmanagementtable #buttonscell p.arrow_button input[name='add']").attr("value", addtext);
                            $(".groupmanagementtable #buttonscell p.arrow_button input[name='remove']").attr("value", removetext);
                        }
                    }
                    // Execute on load
                    checkWidth();
                    // Bind event listener
                    $(window).resize(checkWidth);
                });

                /**
                 * Add needed accessibility for tabs inside Snap.
                 * This makes use of Bootstrap accessible tab panel with WAI-ARIA with the arrow keys binding codes.
                 */
                function Tabpanel(id) {
                    this._id = id;
                    this.$tpanel = $('#' + id);
                    this.$tabs = this.$tpanel.find('.tab');
                    this.$panels = this.$tpanel.find('.tab-pane');
                    this.bindHandlers();
                    this.init();
                }

                Tabpanel.prototype.keys = {
                    left: 37,
                    up: 38,
                    right: 39,
                    down: 40
                };

                Tabpanel.prototype.init = function() {
                    var $tab;
                    this.$panels.attr('aria-hidden', 'true');
                    this.$panels.removeClass('active in');
                    $tab = this.$tabs.filter('.active');
                    if ($tab === undefined) {
                        $tab = this.$tabs.first();
                        $tab.addClass('active');
                    }
                    this.$tpanel
                        .find('#' + $tab.find('a').attr('aria-controls'))
                        .addClass('active in').attr('aria-hidden', 'false');
                };

                Tabpanel.prototype.switchTabs = function($curTab, $newTab) {
                    var $curTabLink = $curTab.find('a'),
                        $newTabLink = $newTab.find('a');
                    $curTab.removeClass('active');
                    $curTabLink.attr('tabindex', '-1').attr('aria-selected', 'false');
                    $newTab.addClass('active');
                    $newTabLink.attr('aria-selected', 'true');
                    this.$tpanel
                        .find('#' + $curTabLink.attr('aria-controls'))
                        .removeClass('active in').attr('aria-hidden', 'true');
                    this.$tpanel
                        .find('#' + $newTabLink.attr('aria-controls'))
                        .addClass('active in').attr('aria-hidden', 'false');
                    $newTabLink.attr('tabindex', '0');
                    $newTabLink.focus();
                };

                Tabpanel.prototype.bindHandlers = function() {
                    var self = this;
                    this.$tabs.keydown(function(e) {
                        return self.handleTabKeyDown($(this), e);
                    });
                    this.$tabs.click(function(e) {
                        return self.handleTabClick($(this), e);
                    });
                };

                Tabpanel.prototype.handleTabKeyDown = function($tab, e) {
                    var $newTab, tabIndex;
                    switch (e.keyCode) {
                        case this.keys.left:
                        case this.keys.up: {
                            tabIndex = this.$tabs.index($tab);
                            if (tabIndex === 0) {
                                $newTab = this.$tabs.last();
                            }
                            else {
                                $newTab = this.$tabs.eq(tabIndex - 1);
                            }
                            this.switchTabs($tab, $newTab);
                            e.preventDefault();
                            return false;
                        }
                        case this.keys.right:
                        case this.keys.down: {
                            tabIndex = this.$tabs.index($tab);
                            if (tabIndex === this.$tabs.length-1) {
                                $newTab = this.$tabs.first();
                            }
                            else {
                                $newTab = this.$tabs.eq(tabIndex + 1);
                            }
                            this.switchTabs($tab, $newTab);
                            e.preventDefault();
                            return false;
                        }
                    }
                };

                Tabpanel.prototype.handleTabClick = function($tab) {
                    var $oldTab = this.$tpanel.find('.tab.active');
                    this.switchTabs($oldTab, $tab);
                };
            },

            /**
             * Custom form error event handler to manipulate the bootstrap markup and show
             * nicely styled errors in an mform focusing the necessary elements in the form.
             */
            enhanceform: function(elementid) {
                var element = document.getElementById(elementid);
                $(element).on(Event.Events.FORM_FIELD_VALIDATION, function(event, msg) {
                    event.preventDefault();
                    var parent = $(element).closest('.form-group');
                    var feedback = parent.find('.form-control-feedback');
                    var invalidinput = parent.find('input.form-control.is-invalid');

                    // Sometimes (atto) we have a hidden textarea backed by a real contenteditable div.
                    if (($(element).prop("tagName") == 'TEXTAREA') && parent.find('[contenteditable]')) {
                        element = parent.find('[contenteditable]');
                    }
                    if (msg !== '') {
                        parent.addClass('has-danger');
                        parent.data('client-validation-error', true);
                        $(element).addClass('is-invalid');
                        $(element).attr('aria-describedby', feedback.attr('id'));
                        $(element).attr('aria-invalid', true);
                        invalidinput.attr('tabindex', 0);
                        feedback.html(msg);

                        // Only display and focus when the error was not already visible.
                        if (!feedback.is(':visible')) {
                            feedback.show();
                            feedback.focus();
                        }
                    } else {
                        if (parent.data('client-validation-error') === true) {
                            parent.removeClass('has-danger');
                            parent.data('client-validation-error', false);
                            $(element).removeClass('is-invalid');
                            $(element).removeAttr('aria-describedby');
                            $(element).attr('aria-invalid', false);
                            feedback.hide();
                        }
                    }
                });
            }
        };
    }
);
