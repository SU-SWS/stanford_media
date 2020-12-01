# [Stanford Media](https://github.com/SU-SWS/stanford_media)
##### Version: 8.x
[![CircleCI](https://circleci.com/gh/SU-SWS/stanford_media.svg?style=svg)](https://circleci.com/gh/SU-SWS/stanford_media)

Maintainers: [pookmish](https://github.com/pookmish)

Changelog: [CHANGELOG.md](CHANGELOG.md)

Description
---

The Stanford Media module is used to enhance the Drupal 8 core media management. The enhancements are listed below.
This has been confirmed to work with ckeditor.

### Field Formatters
In Drupal Core there is no way to select an image style on a media field. The provided field formatters pass in
an image style or responsive image style into the display mode so that a desired image style is easier to display.

### Embed Alter Plugins
When an media entity is being embedded into a wysiwyg, the core media implementation does not provide the user with
certain customizations. The plugins (found in `src/Plugin/MediaEmbedDialog`) target specific media types and
adds necessary customizations and pre-rendering in order to obtain the desired outcome.

### Bulk Upload Page
This uses the above Dropzone Upload plugin. It allows a user to drag and drop as many allowed files as they would like.
It will then provide a form for each item to allow the user to customize image alt texts, item titles, etc.

### Media Duplicate Validation
To try to reduce the amount of duplicate images, the media duplicate validation will compare newly uploaded images and
documents and compare them to existing items. If an item is similar enough, the user will be presented with the options
to use an existing item, or continue to add the new item. Additional duplication plugins can be added when necessary.

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

1. Add the Media Library button to the WYSIWYG as needed and check the box "Embed media".
1. If the WYSIWYG is configured to limit allowed HTML Tags, ensure the the below is added to the list of allowed tags:
`<drupal-media data-entity-type data-entity-uuid data-caption data-align data-* class>`
   * Although `data-*` would allow all data attributes and you wouldn't need to add any others, the UI form validation will throw an error without the above attributes.
   * `data-caption` is only required if captions are allowed. This has to be added and `data-*` cant be relied on due to hard coded checking of the embed form.
   * `data-align` is only required if the media can be aligned. Similar issue as `data-align` above.


Troubleshooting
---

If you are experiencing issues with this module try reverting the configuration files. If you are still experiencing
issues try posting an issue on the GitHub issues page.

Developer
---

Development tools: To build and compile the CSS, Javascript, and Image assets required to make this theme great, you will need to have npm and nvm installed.
Navigate to the root of the theme then.

Run:
```
nvm use
npm install
```

This project uses webpack to assemble the assets for this theme. To compile sass, javascript and push all assets in to place:

Run:
```
npm run publish
```

This script will compile all assets from `/src` into `/dist`.

Check out `package.json` for additional npm scripts and functionality.

Contribution / Collaboration
---

You are welcome to contribute functionality, bug fixes, or documentation to this module. If you would like to suggest a
fix or new functionality you may add a new issue to the GitHub issue queue or you may fork this repository and submit a
pull request. For more help please see [GitHub's article on fork, branch, and pull requests](https://help.github.com/articles/using-pull-requests)


Releases
---

Steps to build a new release:
- Checkout the latest commit from the `8.x-2.x` branch.
- Create a new branch for the release.
- Commit any necessary changes to the release branch.
- Make a PR to merge your release branch into `master`
- Give the PR a semver-compliant label, e.g., (`patch`, `minor`, `major`)
- When the PR is merged to `master`, a new tag will be created automatically, bumping the version by the semver label.
- The github action is built from: [semver-release-action](https://github.com/K-Phoen/semver-release-action), and further documentation is available there.
