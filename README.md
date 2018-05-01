# [Stanford Media](https://github.com/SU-SWS/stanford_media)
##### Version: 8.x-1.x

Maintainers: [pookmish](https://github.com/pookmish)

Changelog: [CHANGELOG.md](CHANGELOG.md)

Description
---

The Stanford Media module is used to enhance the Drupal 8 core media management. The enhancements are listed below.
This has been confirmed to work with ckeditor.

### Entity Browsers

This module provides several different entity browsers:
 - Media Browser: All media types
 - File Browser: Only file media types
 - Image Browser: Only image media types
 - video browser: Only video media types.
 
### Media Field Formatter
A field formatter for any entity reference fields that target media entities. This allows the above entity browsers
to be used on content types. If the target entity is an image, it will allow the rendering to use an image style or a
responsive image style. This helps simplify the display settings of a content type and also eliminates the need for
various joins and relationships in views.

### Embed Alter Plugins
When an media entity is being embedded into a wysiwyg, the core media implementation does not provide the user with
certain customizations. The plugins (found in `src/Plugin/MediaEmbedDialog`) target specific media types and uses and
adds necessary customizations and pre-rendering in order to obtain the desired outcome.

### Entity Browser Plugins
1. Embed Code: Currently this provides the user the ability to input a video url and it will create a video media entity
    if the video provider is available.
2. Dropzone Upload: Allows for a drag and drop upload capability. The upload is limited to a single item in WYSIWYG uses
    but if using on a field, the upload is limitied to the configured number of items as defined by the field.

### Bulk Upload Page
This uses the above Dropzone Upload plugin. It allows a user to drag and drop as many allowed files as they would like.
It will then provide a form for each item to allow the user to customize image alt texts, item titles, etc.

### Automatic Media Creation
If a content type is using a traditional image field or uses the video embed field without implementing the media
browsers above, upon submission of the form, a media entity will be generated automatically. This allows for reusable
media entities in other areas of the site, such as in the WYSIWYG.

Installation
---

Install this module like any other module. [See Drupal Documentation](https://drupal.org/documentation/install/modules-themes/modules-8)
If using this module on an existing site with media module previously enabled, it will over-ride existing media module 
configurations. Take extreme care if media entities already exist prior to this module.

When using composer to declare this as a dependency, custom modifications are needed for the dropzone library. Add or
modify the root composer.json file. Below is a possible example of what to add for the dropzone library to be installed
into the correct directory.

```
"extra": {
    "custom-installer": {
        "docroot/libraries/{$name}/": [
            "enyo/dropzone",
        ]
    }
}
```

When installing this module, it creates media entity types. One issue is when attempting to install the module on an
install profile such as lightning or the standard install profiles. Each of these include media entity type 
configurations which conflict with the ones in this module. Extra steps are necessary if existing entity bundles already
exist. One solution is to temporarily move all the config files in this module into the `config/optional` directory and
then proceed with installation. This should create any missing media bundles and create the entity browsers that are
included with this module. The reason for the conflicts is due to dependencies of other modules. Other modules and other
config files can declare dependency on the configurations of this module. If all configurations of this module were
placed in the `config/optional` directory, it would cause dependency issues during module/profile installation.

Configuration
---

1. Add the Entity Embed button to the WYSIWYG as needed and check the box "Display embedded entities".
2. It is also suggested to check the box "Linkit URL converter" and place the Linkit converter to process after
displaying the embedded entities.
3. If the WYSIWYG is configured to limit allowed HTML Tags, ensure the the below is added to the list of allowed tags:
`<drupal-entity data-entity-type data-entity-uuid data-entity-embed-display data-entity-embed-display-settings 
data-align data-caption data-embed-button class>`


Troubleshooting
---

If you are experiencing issues with this module try reverting the configuration files. If you are still experiencing 
issues try posting an issue on the GitHub issues page.

Developer
---

If you wish to develop on this module you will most likely need to compile some new css. Please use the sass structure
provided and compile with the sass compiler packaged in this module. To install:

```
npm install
grunt watch
 or
grunt devmode
```

Contribution / Collaboration
---

You are welcome to contribute functionality, bug fixes, or documentation to this module. If you would like to suggest a
fix or new functionality you may add a new issue to the GitHub issue queue or you may fork this repository and submit a 
pull request. For more help please see [GitHub's article on fork, branch, and pull requests](https://help.github.com/articles/using-pull-requests)