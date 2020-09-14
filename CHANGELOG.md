# Stanford Media

8.x-2.8
--------------------------------------------------------------------------------
_Release Date: 2020-09-14_

- Merge pull request #61 from SU-SWS/D8CORE-2521 (2a1f365)
- Merge pull request #64 from SU-SWS/rm-field-perms (4ed577f)
- Merge branch 'D8CORE-2521' into rm-field-perms (c3f92eb)
- Merge pull request #65 from SU-SWS/feedback-D8CORE-2521 (a59c9cf)
- fixed tests (f6c50b4)
- Merge branch 'feedback-D8CORE-2521' of github.com:SU-SWS/stanford_media into feedback-D8CORE-2521 (3ff5a2b)
- simplified embed form (a1140fd)
- cleanup feedback suggestions (3a28492)
- D8CORE-2527: completed mocks and test for field formatter (9c030e4)
- simplified embed form (f3b5962)
- cleanup feedback suggestions (be4c101)
- fixup! removed field_permissions. (0d8bcc7)
- D8CORE-2527: more coverage, codeclimate stuff. (c97a2a7)
- D8CORE-2527: more test coverage, refactored mediatype name out of form, other fixups. (e41df4c)
- D8CORE-2527: codeclimate fixups (649a7e5)
- D8CORE-2527: more tests, fixed form (a07f964)
- D8CORE-2527: moar tests (c156765)
- D8CORE-2527: added validator test (06ddd2e)
- D8CORE-2527: added field permissions to composer.json (909b789)
- D8CORE-2527: tests continued. (2671bf4)
- D8CORE-2527: trying to make tests work (a28dcd9)
- D8CORE-2527: zero out tests (e28c308)
- D8CORE-2521: adding install configs, codeclimate fixups. (b26e521)
- D8CORE-2527: Install configs added for test support (f94c389)
- D8CORE-2527: Added scaffolding for tests (f744641)
- D8CORE-2521: Fixup! a little cleanup. (8881387)
- D8CORE-2521: fixup! field formatter refactor (9d5af2b)
- D8CORE-2521: fixup! Added configs to prevent using field names directly. (0b7e788)
- Bump decompress from 4.2.0 to 4.2.1 (#63) (b224087)
- D8CORE-2521: fixup on constraint validator (973b50a)
- D8CORE-2521: fixup for dependency injection in form (ac267e8)
- D8CORE-2521: fixups! (174455f)
- D8CORE-2521: fixups! (7813d53)
- D8CORE-2521: fixup! (19c5f59)
- D8CORE-2521: fixups! (58cc6f2)
- Merge changes from PR feedback (fbe62a7)
- D8CORE-2521: removing line (09089e7)
- Apply suggestions from code review (d606dd2)
- D8CORE-2521: correcting a11y iframe problems with oEmbeds (5352354)
- D8CORE-2521: fixing bugs with wysiwyg media library form (8e8ff4d)
- D8CORE-2521: Removing redundant overrides in the Embeddable object (2cd2db9)
- D8CORE-2521: removing redundant overrides in the form (5966b41)
- D8CORE-2521: linting (2df70c7)
- D8CORE-2521: Changed field formatter for embeddables to handle both oEmbeds and unstructured embeds to get more granular control. (13dcbb0)
- D8CORE-2521: Adding permissions check for unstructured embed in WYSIWYG media browser form (cdfd3ec)
- D8CORE-2521: oEmbeds work, freeform embeds work, form constraints work. Added additional oEmbed providers (aaa2cc4)
- D8CORE-2521: continuing work on embeddables (fe8c042)
- D8CORE-2499 Updated composer license (#62) (384a274)
- work in progress (14b7686)
- D8CORE-2521: embedded form works in wysiwyg (15f00e6)
- D8CORE-2521: adding form for embeddables (3389f73)
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
