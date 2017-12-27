# Change Log

## 2.0.9
- Fix rollback migrations.

## 2.0.8
- Fix migrations dependencies for future UF Version.

## 2.0.7
- Fix migrations.

## 2.0.6
- New `forProfileFieldsValue` scope (Usage :: `$groups = Group::forProfileFieldsValue($slug, $value);`) and `getProfileFieldsForSlug` custom mutator (Usage :: `$fieldValue = Group::getProfileFieldsForSlug($slug);`) both User and Group custom Models.

## 2.0.5
- Update controller from core one

## 2.0.4
- Fix db issue with group profile (Need to run new migration)

## 2.0.3
- Updated Readme
- Fix issue with cache

## 2.0.2
- Fix issue where field value wound't display in user and group profile

## 2.0.1
- Updated FormGenerator dependencies

## 2.0.0
- First official release
- Updated for UserFrosting v4.1.x

## 0.0.1
- Initial beta release
