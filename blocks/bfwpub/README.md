BFW LMS Moodle integration
==========================
This is the Moodle 2 BFW-LMS integration plugin which integrates Moodle with BFW LMS (http://bfwpub.com/catalog/)
This plugin will work with Moodle 2.x. It is developed as a Moodle plugin/block.

Moodle Compatibility
--------------------
This plugin will work with Moodle 2.x. It is developed as a Moodle plugin/block.

IMS Basic LTI
-------------
The BFW integration requires Moodle to have IMS Basic LTI support. Moodle 2.2 or higher includes Basic LTI support
(referred to as "External Tool" in the UI).

    http://docs.moodle.org/22/en/External_tool

If you are using a version prior to 2.2 then you have to install the Moodle Basic LTI module from here:

     http://code.google.com/p/basiclti4moodle/

Installation instructions and the current version are available in the downloads section of that site.

This integration requires access to a BFW-LMS course. Please contact salessupport@bfwpub.com for details.

Download Binary
---------------
The plugin will be made available via the moodle plugins listing::

    http://moodle.org/plugins/view.php?plugin=block_bfwpub

It can also be downloaded from the project site::

    http://lmslink.bfwpub.com/client/index.html

Install
-------
To install this plugin just extract the contents into your server dir MOODLE_HOME/blocks (so you have MOODLE_HOME/blocks/bfwpub).

Once the plugin is installed, you can place the block into your instance.
This is the recommended way to setup the block::

    1. Login to your Moodle instance as an admin
    2. Click on Site Administration > Notifications
    3. Confirm the installation of the bfwpub block (continue confirmation until complete)

See the Moodle docs for help installing plugins/blocks::

    http://docs.moodle.org/en/Installing_contributed_modules_or_plugins

Unit Tests
----------
If you are interested you can run the unit tests for the plugin to verify that it is compatible with your installation.
If all the tests pass then you can be confident that the plugin will work correctly.
NOTE: You need to have at least 1 user (other than the admin) in your moodle instance to run the tests successfully.
Go to the following URL in your moodle instance when logged in as an admin::

    /admin/tool/unittest/index.php?codecoverage=0&path=%2Fblocks%2Fbfwpub

Configuration
-------------
The configuration of the block is handled in the typical Moodle way. You must login as an administrator and then go to::

    Site Administration > Plugins > Blocks > BFW LMS

Usage
-----
The plugin itself is only used by the grade sync mechanism so there is no UI or user facing interface.
The basic LTI portion does need to be setup for each course. BFW sales support or technical support will provide the instructions.

REST data feeds
---------------
The REST data feeds for the block are documented and located at::

    /blocks/bfwpub/rest.php

Help
----
Send questions or comments to:
salessupport@bfwpub.com
Technical support is available from:
BFW.TechnicalSupport@macmillan.com


This document is in `reST (reStructuredText) <http://docutils.sourceforge.net/rst.html>`_ format
and can be converted to html using the `online converter <http://www.tele3.cz/jbar/rest/rest.html>`_
or the `rst2a converter api <http://rst2a.com/api/>`_ or a command line tool (rst2html.py README README.html)

-Aaron Zeckoski (azeckoski @ vt.edu)
