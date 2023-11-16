# HTTP Message Change Log

## 1.1.4 - 2023.11.16

### Fixed

- [#29](https://github.com/httpsoft/http-message/pull/29) Fixes error handling to `HttpSoft\Message\StreamTrait::getContents()`.

## 1.1.3 - 2023.11.15

### Fixed

- [#28](https://github.com/httpsoft/http-message/pull/28) Fixes error handling to `HttpSoft\Message\StreamTrait::getContents()`.

## 1.1.2 - 2023.11.15

### Fixed

- [#27](https://github.com/httpsoft/http-message/pull/27) Fixes error handling on stream reading to `HttpSoft\Message\StreamTrait`.

## 1.1.1 - 2023.05.06

### Fixed

- [#22](https://github.com/httpsoft/http-message/pull/22) Fixes already encoded userinfo to `HttpSoft\Message\Uri`.
- [#23](https://github.com/httpsoft/http-message/pull/23) Fixes use of UTF-8 characters to host in `HttpSoft\Message\Uri`.
- [#24](https://github.com/httpsoft/http-message/pull/24) Fixes header values normalization by trimming in `HttpSoft\Message\MessageTrait`.

## 1.1.0 - 2023.05.05

### Changed

- [#21](https://github.com/httpsoft/http-message/pull/21) Allows `psr/http-message` package version 2.

### Fixed

- [#19](https://github.com/httpsoft/http-message/pull/19) Fixes min and max allowed ports in exception message to `HttpSoft\Message\Uri`.

## 1.0.12 - 2023.04.17

### Fixed

- [#18](https://github.com/httpsoft/http-message/pull/18) Fixes validation of header names and values to `HttpSoft\Message\MessageTrait`.

## 1.0.11 - 2023.04.02

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#15](https://github.com/httpsoft/http-message/pull/15) Fixes normalizing leading slashes for `getPath()` and `__toString()` methods to `HttpSoft\Message\Uri` class.

## 1.0.10 - 2022.07.21

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#13](https://github.com/httpsoft/http-message/pull/13) Adds a mode for opening a stream to `HttpSoft\Message\UploadedFile`.

## 1.0.9 - 2021.07.13

### Added

- [#11](https://github.com/httpsoft/http-message/pull/11) adds caching of stream metadata to improve performance when calling the `HttpSoft\Message\StreamTrait` methods again: `getSize()`, `isSeekable()`, `isWritable()`, `isReadable()`.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#10](https://github.com/httpsoft/http-message/pull/10) adds unit tests and Psalm improvements, updates of workflow actions.

## 1.0.8 - 2021.02.20

### Added

- Adds lazy `HttpSoft\Message\Stream` creation to `HttpSoft\Message\MessageTrait` to improve performance. 
- Adds integration tests against PSR-7 specification, for this purpose the package `php-http/psr7-integration-tests` is used as a development dependency.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Fixes the behavior of some `HttpSoft\Message\Uri` methods when passing them a single zero as a string, now `'0'` is not considered an empty value.

## 1.0.7 - 2020.12.16

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#9](https://github.com/httpsoft/http-message/pull/9) fixes throwing the `\RuntimeException` exception when creating a resource if an empty string was passed.

## 1.0.6 - 2020.12.12

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updates development dependencies.
- Fixes `HttpSoft\Message\StreamTrait::init()` to improve the performance of creation of the resource.

## 1.0.5 - 2020.09.16

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#6](https://github.com/httpsoft/http-message/pull/6) changes the thrown exception from `\InvalidArgumentException` to `\RuntimeException` when creating a stream if the file cannot be opened.

## 1.0.4 - 2020.09.06

### Added

- [#4](https://github.com/httpsoft/http-message/pull/4) adds implementations declaration to the `composer.json`.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.3 - 2020.08.28

### Added

- Adds files to `.github` folder (ISSUE_TEMPLATE, PULL_REQUEST_TEMPLATE.md, CODE_OF_CONDUCT.md, SECURITY.md).

### Changed

- Moves static analysis and checking of the code standard to an independent github action.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.2 - 2020.08.26

### Added

- Adds support OS Windows to build github action.
- [#3](https://github.com/httpsoft/http-message/pull/3) adds `infection/infection` package as dev dependency and mutation action to github workflows for perform mutation testing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#1](https://github.com/httpsoft/http-message/pull/1) fixes error messages.

## 1.0.1 - 2020.08.25

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Moves Psalm issue handlers from psalm.xml to docBlock to appropriate methods.

## 1.0.0 - 2020.08.23

- Initial stable release.
