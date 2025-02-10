# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0](https://github.com/microsoft/kiota-php/compare/microsoft-kiota-serialization-json-v1.4.0...microsoft-kiota-serialization-json-v1.5.0) (2025-02-10)


### Features

* add kiota bundle ([575e3bc](https://github.com/microsoft/kiota-php/commit/575e3bc147dfcdd02128db5cad46b82959d3e38e))
* add release please configuration to monorepo ([57de3a2](https://github.com/microsoft/kiota-php/commit/57de3a20091d1cd349d3c4b0e840920ac3a57d75))


### Bug Fixes

* Json serialization tests namespace ([e80465e](https://github.com/microsoft/kiota-php/commit/e80465ea81e2fba6c524ce664a2fe18c867219d4))
* removes call to addcslashes in getStringValue() functions ([f7097a1](https://github.com/microsoft/kiota-php/commit/f7097a1e13c71f5fe4246d61dc806ac7300412ea))
* removes call to addcslashes in getStringValue() functions ([64db05d](https://github.com/microsoft/kiota-php/commit/64db05d895bf6e1b09462dbd184665a6e7b3a66f))
* subproject config & CI ([673beef](https://github.com/microsoft/kiota-php/commit/673beef4ae3f99c94a7730bb3810d4a1abdf27d5))

## [1.3.1]

### Added

### Changed
- fix(serialization): Float serialization also accepts integer values. [#85](https://github.com/microsoft/kiota-serialization-json-php/pull/85)

## [1.3.0]

### Added

### Changed
- fix(logic): Don't cast types since this might introduce some logical bugs. Make sure values match possible values for that type.
- fix(serialization): Fix how composed types are handled.

## [1.0.1]

### Changed
- Exclude non-prod files from shipped archive

## [1.0.0] - 2023-11-01

### Changed
- Return only non-null values within deserialized collections
- Bumped abstractions to 1.0.0
- Mark package as stable

## [0.7.0] - 2023-10-30

### Added
- Adds CHANGELOG. [#47](https://github.com/microsoft/kiota-serialization-json-php/pull/47)

### Changed
- Disabled PHP-HTTP discovery plugin. [#49](https://github.com/microsoft/kiota-serialization-json-php/pull/49)
- Bumps Kiota Abstractions dependency version. [#52](https://github.com/microsoft/kiota-serialization-json-php/pull/52)

## [0.6.0] - 2023-06-29

### Added
- Disable pipeline runs for forks. [#38](https://github.com/microsoft/kiota-serialization-json-php/pull/38)

### Changed
- Handle null values when serializing intersection wrappers. [#39](https://github.com/microsoft/kiota-serialization-json-php/pull/39)

## [0.5.1] - 2023-06-12

### Changed
- Fix DateTime serialization. [#35](https://github.com/microsoft/kiota-serialization-json-php/pull/35)

## [0.5.0] - 2023-05-18

### Changed
- Bump abstractions. [#29](https://github.com/microsoft/kiota-serialization-json-php/pull/29)

## [0.4.3] - 2023-04-13

### Added
- Support deserializing objects to stream. [#22](https://github.com/microsoft/kiota-serialization-json-php/pull/22)

## [0.4.2] - 2023-03-22

### Changed
- Fix static analysis issues. [#17](https://github.com/microsoft/kiota-serialization-json-php/pull/17)
- Fix PHPStan failure when getting object value from JsonParseNode. [#18](https://github.com/microsoft/kiota-serialization-json-php/pull/18)

## [0.4.1] - 2023-03-07

### Changed
- fix: tab escaping. [#15](https://github.com/microsoft/kiota-serialization-json-php/pull/15)

## [0.4.0] - 2023-02-21

### Added
- Composed types (De)serialization support. [#6](https://github.com/microsoft/kiota-serialization-json-php/pull/6)
- Add SonarCloud Coverage reporting. [#10](https://github.com/microsoft/kiota-serialization-json-php/pull/10)
- Change workflow to use strategy matrix for PHP versions. [#8](https://github.com/microsoft/kiota-serialization-json-php/pull/8)

### Changed
- Bump Abstractions version. [#13](https://github.com/microsoft/kiota-serialization-json-php/pull/13)


*For previous releass, please see our [Release Notes](https://github.com/microsoft/kiota-serialization-json-php/releases)*
