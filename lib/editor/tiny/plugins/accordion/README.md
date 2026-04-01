[![ci](https://github.com/catalyst/moodle-tiny_accordion/actions/workflows/ci.yml/badge.svg?branch=MOODLE_405_STABLE)](https://github.com/catalyst/moodle-tiny_accordion/actions/workflows/ci.yml?branch=MOODLE_405_STABLE)

moodle-tiny_accordion
=====================

What is this
------------

This TinyMCE plugin for Moodle extends the editor's functionality by enabling users to create, edit, and manage accordion components directly within the content area. It provides an interface for inserting structured, collapsible sections, allowing content to be organised in a clear and user-friendly manner without requiring manual HTML editing

## Demo

![Demo](pix/demo.gif)

Branches
------------

| Moodle version    | Branch             |
| ----------------- | ------------------ |
| Moodle 4.5  | `MOODLE_405_STABLE` |

Installation
------------

Install the plugin the same way as any standard Moodle plugin (https://docs.moodle.org/405/en/Installing_plugins), either via the Moodle Plugin Directory or by cloning the repository into your Moodle source code:

    `git clone git@github.com:catalyst/moodle-tiny_accordion.git lib/editor/tiny/plugins/accordion`


Settings
------------
Once installed, the plugin can be configured under **Site Administration → Plugins → Text editors → TinyMCE editor → Accordion**.
 
| Setting | Description | Default |
| ------- | ----------- | ------- |
| **Show accordion icons in toolbar** | Display both the accordion and remove accordion buttons in the TinyMCE toolbar. | Yes |
| **Show remove accordion icon in toolbar** | Display the remove accordion button in the TinyMCE toolbar. Only applies when accordion icons are enabled. | Yes |
| **Toolbar group** | Select which toolbar group the accordion icons appear in. Only applies when accordion icons are enabled. | Content |
 
> **Note:** Disabling the accordion icons only hides the toolbar buttons. The accordion plugin remains active so that existing accordion content continues to render correctly in the editor.

References
------------

This plugin implementation is based on the following official documentation:

- **TinyMCE Accordion Documentation**  
  https://www.tiny.cloud/docs/tinymce/latest/accordion/

- **Moodle TinyMCE Plugin Development Documentation**  
  https://moodledev.io/docs/4.5/apis/plugintypes/tiny


## Support

If you have issues please log them in [GitHub](https://github.com/catalyst/moodle-tiny_accordion).

Please note our time is limited, so if you need urgent support or want to sponsor a new feature then please contact [Catalyst IT Australia](https://www.catalyst-au.net/).


Acknowledgements
================

Sponsored by
------------

[![Central Queensland University](pix/cqu.png?raw=true)](https://www.cqu.edu.au/)


Developed by
------------
[![Catalyst IT Australia](pix/catalyst.png?raw=true)](https://www.catalyst-au.net/)