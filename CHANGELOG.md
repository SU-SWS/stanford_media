# Stanford Media

11.1.1
--------------------------------------------------------------------------------
_Release Date: 2024-08-28_

- Improve message when editing media that is used in some location.

11.1.0
--------------------------------------------------------------------------------
_Release Date: 2024-06-26_

- D11 upgrades, requires D10.3
- Logger syntax bug fix.

11.0.3
--------------------------------------------------------------------------------
_Release Date: 2024-03-14_

- Update PHPUnit tests


11.0.2
--------------------------------------------------------------------------------
_Release Date: 2024-03-14_

- Set Oembed lazy loading link text for embedded videos

11.0.1
--------------------------------------------------------------------------------
_Release Date: 2024-01-08_

- Update hook to remove filter plugin.

11.0.0
--------------------------------------------------------------------------------
_Release Date: 2024-01-08_

- Removed deprecated filter plugin.
- Removed dependency on transliterate_filenames in favor of D10.2 settings.

10.1.4
--------------------------------------------------------------------------------
_Release Date: 2023-12-18_

- Update dropzone widget for D10.2

10.1.3
--------------------------------------------------------------------------------
_Release Date: 2023-12-13_

- Update inline_entity_form

10.1.2
--------------------------------------------------------------------------------
_Release Date: 2023-12-08_

- Uncommented line that adds media styling.

10.1.1
--------------------------------------------------------------------------------
_Release Date: 2023-11-28_

- HSD8-1527 Fetch oembed data and title on client side if none exists (#144)

10.1.0
--------------------------------------------------------------------------------
_Release Date: 2023-09-20_

- HSD8-1416 Media usage page with links to the the parent content
- D8CORE-6976: removed the italics on the caption

9.1.0
--------------------------------------------------------------------------------
_Release Date: 2023-09-07_

- Removed drupal core patch that was merged into 10.1 drupal core.

9.0.1
--------------------------------------------------------------------------------
_Release Date: 2023-06-07_

- Allow installing inline_entity_form > 2.0.0

9.0.0
--------------------------------------------------------------------------------
_Release Date: 2023-02-21_

- Removed deprecated code uses to prepare for Drupal 10

8.3.5
--------------------------------------------------------------------------------
_Release Date: 2022-07-28_

- HSD8-1350 Restore the color profile after stripping the metadata
- Remove max length on the embed code field formatter settings


8.3.4
--------------------------------------------------------------------------------
_Release Date: 2022-07-28_

- HSD8-1340 Clean up files when a media item is deleted (#132)
- HSD8-1338 Add twitter embed code validator (#131)

8.3.3
--------------------------------------------------------------------------------
_Release Date: 2022-07-08_

- Fixup for iframe validators constant value
- Eliminated deprecated functions and methods
- D8CORE-5128 provide embed code validators for a few iframe services (#127)
- fixed composer namespace to lowercase

8.3.2
--------------------------------------------------------------------------------
_Release Date: 2022-06-15_

- D8CORE-5963 Improve "Decorative" approach to images

8.x-3.1
--------------------------------------------------------------------------------
_Release Date: 2022-05-06_

- Fix bulk upload form for any private file paths.

8.x-3.0
--------------------------------------------------------------------------------
_Release Date: 2022-05-02_

- Added Transliteration Filenames module dependency


8.x-2.24
--------------------------------------------------------------------------------
_Release Date: 2022-03-17_

- D8CORE-5180 D8CORE-5227 Remove alt text on people images (#118)
- D8CORE-5410 Apply the modified alt help text to embedded images (#117)


8.x-2.23
--------------------------------------------------------------------------------
_Release Date: 2022-02-08_

- Updated unit tests for latest drupal core (#114)


8.x-2.22
--------------------------------------------------------------------------------
_Release Date: 2022-01-27_

- D8CORE-5241 Adjust image alt text description (#112)
- Adjust validation to work with D9.3
- Updated tests for D9.3 (#110)
- Enable oembed providers via update hook


8.x-2.21
--------------------------------------------------------------------------------
_Release Date: 2021-11-19_

- D8CORE-4878: added oEmbed Providers (#108)


8.x-2.20
--------------------------------------------------------------------------------
_Release Date: 2021-10-21_

- Fixed error when adding new media types
- D8CORE-4816 Remove hard coded allowed tags in favor of config (#106)


8.x-2.19
--------------------------------------------------------------------------------
_Release Date: 2021-10-08_

- D8CORE-4698 Fix image orientation and strip tags updon upload (#104)
- Changed logic with embeddable validation
- Adjust duplicate media validation to only compare against the same bundles.
- Add Embeddable validation plugin for allowing content editor embed entry (#103)

8.x-2.18
--------------------------------------------------------------------------------
_Release Date: 2021-08-03_

- Removed drupal core patch that's been fixed

8.x-2.17
--------------------------------------------------------------------------------
_Release Date: 2021-06-21_

- D8CORE-4392 Check for media access on bulk upload form

8.x-2.16
--------------------------------------------------------------------------------
_Release Date: 2021-06-11_

- D8CORE-3967 Edited embeddable retains title, tests (#97) (8f701e0)

8.x-2.15
--------------------------------------------------------------------------------
_Release Date: 2021-04-09_

- Provide a message on media form and lock default field media items (#92) (d7610b7)

8.x-2.14
--------------------------------------------------------------------------------
_Release Date: 2021-03-05_

- Updated linkit module (#90) (3a70fde)

8.x-2.13
--------------------------------------------------------------------------------
_Release Date: 2021-02-08_

- D8CORE-3425 Patch drupal core for better embeddable handling (#88) (86a2fa2)
- D9 automated Tests (#85) (9661793)
- Remove the filter caption template in favor of preprocess hook' (#87) (7ae18ee)

8.x-2.12
--------------------------------------------------------------------------------
_Release Date: 2020-12-04_

- D8CORE-2431: adding height field to google form. (#81) (5071ffd)
- D8CORE-2267 Removed caption checkbox from file media (#82) (98fb98b)
- fixed ckeditor style paths (2c4ae9e)
- Adjusted tests to pass on D9 (#79) (cced142)
- D8CORE-2753: convert to webpack and caption edits. (#80) (3b44cf8)
- phpunit void return annoation (0147dd6)
- phpunit void return annoation (ac2c3da)
- D9 Ready (#78) (cd90328)

8.x-2.11
--------------------------------------------------------------------------------
_Release Date: 2020-11-09_

- D8CORE-2428 Add patch for for core to fix media library cardinality validation.

8.x-2.10
--------------------------------------------------------------------------------
_Release Date: 2020-11-06_

- Allow `<a>` tags in embeddable code. (#71) (e58be88)
- Add a check for when the mime type of the image is not known (08af22a)

8.x-2.9
--------------------------------------------------------------------------------
_Release Date: 2020-10-05_

- D8CORE-2514: Added div to the list of allowed tags in unstructured embeds (#69) (50b0b94)

8.x-2.8
--------------------------------------------------------------------------------
_Release Date: 2020-09-14_

- D8CORE-2521: adding form for embeddables (5bbea72)
- Bump decompress from 4.2.0 to 4.2.1 (#63) (b224087)
- D8CORE-2499 Updated composer license (#62) (384a274)
- DEVOPS-000: Removes fixed focal-point patch (#60) (8dcbcd2)
- Bump websocket-extensions from 0.1.3 to 0.1.4 (#57) (8165443)

8.x-2.7
--------------------------------------------------------------------------------
_Release Date: 2020-07-13_

- removed test (a80676b)

8.x-2.6
--------------------------------------------------------------------------------
_Release Date: 2020-05-15_

- D8CORE-000: Enhanced cache tags (#55) (3659d10)
- Set cache keys for image field formatters. (#54) (482faaa)
- Merge branch 'master' into 8.x-2.x (33dee03)

8.x-2.5
--------------------------------------------------------------------------------
_Release Date: 2020-04-14_

- Removed entity usage plugin that is now in the contrib module (#46)
- D8CORE-736 Removed image formatter in favor of a core patch (#47)
- D8CORE-1706: Fixed error when a user cancels uploaded file (#48)
- D8CORE-1706: updated focal point patch (#48)
- D8CORE-1644: Switch to dev branch workflow (#51)
- D8CORE-1223 Added caption field to the image modal in wysiwyg (#50)
- HSD8-752 google forms media type support (#49)
- D8CORE-1197 Google forms media type configs (#52)

8.x-2.4
--------------------------------------------------------------------------------
_Release Date: 2020-03-23_

- Updated focal point patch with latest version of focal point.

8.x-2.3
--------------------------------------------------------------------------------
_Release Date: 2020-03-20_

- D8CORE-1026 D8CORE-1516 Fix error in field formatters (#40)
- D8CORE-1026 Don't modify non-images in field formatter
- Add test to validate no PHP errors when media item being referenced is deleted.

8.x-2.2
--------------------------------------------------------------------------------
_Release Date: 2020-02-27_

- D8CORE-1325 Make dropzone error more visible (#37)

8.x-2.1
--------------------------------------------------------------------------------
_Release Date: 2020-02-21_

- HSD8-758 Fixed number of items available when the field is unlimited
- D8CORE-1343 Use Media name if no description is provided
- D8CORE-1392 Added padding to aligned images
- D8CORE-1409: Remove duplicate source tag from embedded media.

8.x-2.0
--------------------------------------------------------------------------------
_Release Date: 2020-02-19_

- D8CORE-106: Multiple Media Formatter (#28)
- Changed image for circle ci to the PCOV one
- Added .nvmrc and set to 11.14.0
- Updated lots of package.json components

8.x-2.0-alpha3
--------------------------------------------------------------------------------
_Release Date: 2020-02-14_

8.x-2.0-alpha2
--------------------------------------------------------------------------------
_Release Date: 2020-01-22_

- check for entities before deleting them in update process
- Patch focal point for use in media library (#27)
- Dont set responsive image style for non-images
- Bugfix: Prevent php notice by faking another field data (#25)
- D8CORE-638 D8CORE-801 Tweaks to WYSIWYG media (#26)
- changed weekly tests to correct branch
- D8CORE-334: Update responsive breakpoints and image styles. (#23)
- Update stanford_media.info.yml


8.x-2.0-alpha1
--------------------------------------------------------------------------------
_Release Date: 2019-10-30_

- Initial Release using Drupal Core media in 8.8.
- 1.0 release was never made due to its instability and lack of testing coverage.
