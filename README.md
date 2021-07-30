# Extension speed-test

Designed for approximate estimation of the time of code 
execution by the number of iterations.

## Install
```bash
composer require bfg/speed-test --dev
```

## Create speed check
Suppose that we need to check how long it will work out the `bcrypt` 
password encryption function.
Run the following command:
```bash
php artisan make:speed-test bcrypt -t 10 -l "bcrypt(time());"
```
And launch the test:
```bash
php artisan speed:test bcrypt
```

## Config publish
```bash
php artisan vendor:publish --tag=speed-test
```
## Commands

### make:speed-test
Speed test maker
```bash
Usage:
  make:speed-test [options] [--] [<test>]

Arguments:
  test                             The test name

Options:
  -d, --description[=DESCRIPTION]  The description of test
  -t, --times[=TIMES]              Number of iterations [default: "10"]
  -l, --line[=LINE]                Code line in the icted function [default: "//"]
```
Your test will be created in the `tests/Speeds` folder, 
you can change this in config `speed-test.dir`.
> Meaning Description and the number of 
> iterations are indicated in the dock of the test block.

### speed:test
Speed test runner
```bash
Usage:
  speed:test [<test>]

Arguments:
  test                  The name of the test case.
```
> You can specify the class name as a test name, 
> or specify the name of the class together with the "@" method.
