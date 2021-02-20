# HTTP Message

[![License](https://poser.pugx.org/httpsoft/http-message/license)](https://packagist.org/packages/httpsoft/http-message)
[![Latest Stable Version](https://poser.pugx.org/httpsoft/http-message/v)](https://packagist.org/packages/httpsoft/http-message)
[![Total Downloads](https://poser.pugx.org/httpsoft/http-message/downloads)](https://packagist.org/packages/httpsoft/http-message)
[![GitHub Build Status](https://github.com/httpsoft/http-message/workflows/build/badge.svg)](https://github.com/httpsoft/http-message/actions)
[![GitHub Static Analysis Status](https://github.com/httpsoft/http-message/workflows/static/badge.svg)](https://github.com/httpsoft/http-message/actions)
[![Scrutinizer Code Coverage](https://scrutinizer-ci.com/g/httpsoft/http-message/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/httpsoft/http-message/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/httpsoft/http-message/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/httpsoft/http-message/?branch=master)

This package is a lightweight, fast, high-performance and strict implementation of the [PSR-7 HTTP Message](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-7-http-message.md) and [PSR-17 HTTP Factories](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-17-http-factory.md).

The package does not contain any additional functionality other than that defined in the PSR interfaces.

## Documentation

* [In English language](https://httpsoft.org/docs/message).
* [In Russian language](https://httpsoft.org/ru/docs/message).

## Installation

This package requires PHP version 7.4 or later.

```
composer require httpsoft/http-message
```

## Benchmark

| Runs: 30,000         | Guzzle    | HttpSoft  | Laminas   | Nyholm    | Slim      |
|----------------------|-----------|-----------|-----------|-----------|-----------|
| Runs per second      | 15868     | 19544     | 12257     | 19022     | 12117     |
| Average time per run | 0.0630 ms | 0.0512 ms | 0.0816 ms | 0.0526 ms | 0.0825 ms |
| Total time           | 1.8905 s  | 1.5349 s  | 2.4474 s  | 1.5771 s  | 2.4757 s  |

See benchmark at [https://github.com/devanych/psr-http-benchmark](https://github.com/devanych/psr-http-benchmark).

## Usage

> For a description of how to use the package components, see the [PSR-7](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-7-http-message.md) and [PSR-17](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-17-http-factory.md) specifications and [package documentation](https://httpsoft.org/docs/message).

For the convenience of creating requests to the server from PHP superglobals, you can use the [httpsoft/http-server-request](https://github.com/httpsoft/http-server-request) package.

```
composer require httpsoft/http-server-request
```

You can use the [httpsoft/http-runner](https://github.com/httpsoft/http-runner) package to run requests to the server and emit responses, as well as build the [PSR-15](https://github.com/php-fig/http-server-middleware) middleware pipelines.

```
composer require httpsoft/http-runner
```
