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
| Runs per second      | 15422     | 17550     | 13402     | 16588     | 12756     |
| Average time per run | 0.0648 ms | 0.0570 ms | 0.0746 ms | 0.0603 ms | 0.0784 ms |
| Total time           | 1.9452 s  | 1.7094 s  | 2.2384 s  | 1.8085 s  | 2.3517 s  |

See benchmark at [https://github.com/devanych/psr-http-benchmark](https://github.com/devanych/psr-http-benchmark).
