# Change Log

## [3.0.0]
- Added support for FormGenerator 4.0.
- Added automated testing.
- Updated GroupController and UserController to match latest UF version.
- GroupProfileHelper & UserProfileHelper constructor now only requires the necessary services.
- Updated migration support.

## [2.1.1]
- Fix modals on admin panel ([#5]; Thanks @Jamezsss)

## [2.1.0]
- Add support for FormGenerator v3.0.0

## [2.0.9]
- Fix rollback migrations.

## [2.0.8]
- Fix migrations dependencies for future UF Version.

## [2.0.7]
- Fix migrations.

## [2.0.6]
- New `forProfileFieldsValue` scope (Usage :: `$groups = Group::forProfileFieldsValue($slug, $value);`) and `getProfileFieldsForSlug` custom mutator (Usage :: `$fieldValue = Group::getProfileFieldsForSlug($slug);`) both User and Group custom Models.

## [2.0.5]
- Update controller from core one

## [2.0.4]
- Fix db issue with group profile (Need to run new migration)

## [2.0.3]
- Updated Readme
- Fix issue with cache

## [2.0.2]
- Fix issue where field value wound't display in user and group profile

## [2.0.1]
- Updated FormGenerator dependencies

## [2.0.0]
- First official release
- Updated for UserFrosting v4.1.x

## 0.0.1
- Initial beta release

[3.0.0]: https://github.com/lcharette/UF_UserProfile/compare/2.1.1...3.0.0
[2.1.1]: https://github.com/lcharette/UF_UserProfile/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/lcharette/UF_UserProfile/compare/2.0.9...2.1.0
[2.0.9]: https://github.com/lcharette/UF_UserProfile/compare/2.0.8...2.0.9
[2.0.8]: https://github.com/lcharette/UF_UserProfile/compare/2.0.7...2.0.8
[2.0.7]: https://github.com/lcharette/UF_UserProfile/compare/2.0.6...2.0.7
[2.0.6]: https://github.com/lcharette/UF_UserProfile/compare/2.0.5...2.0.6
[2.0.5]: https://github.com/lcharette/UF_UserProfile/compare/2.0.4...2.0.5
[2.0.4]: https://github.com/lcharette/UF_UserProfile/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/lcharette/UF_UserProfile/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/lcharette/UF_UserProfile/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/lcharette/UF_UserProfile/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/lcharette/UF_UserProfile/compare/0.0.1...2.0.0
[#5]: https://github.com/lcharette/UF_UserProfile/pull/5
