# Extension benchmark

Designed for approximate estimation of the time of code 
execution by the number of iterations.

## Install
```bash
composer require bfg/speed-test --dev
```

## Create bench check
Suppose that we need to check how long it will work out the `bcrypt` 
password encryption function.
Run the following command:
```bash
php artisan make:bench bcrypt -t 10 -l "bcrypt(time());"
```
And launch the test:
```bash
php artisan benchmark bcrypt
```

## Config publish
```bash
php artisan vendor:publish --tag=speed-test
```
## Commands

### make:bench
Speed test maker
```bash
Usage:
  make:bench [options] [--] [<test>]

Arguments:
  test                             The test name

Options:
  -d, --description[=DESCRIPTION]  The description of test
  -t, --times[=TIMES]              Number of iterations [default: "10"]
  -l, --line[=LINE]                Code line in the icted function [default: "//"]
```
Your test will be created in the `tests/Benchmark` folder, 
you can change this in config `speed-test.dir`.
> Meaning Description and the number of 
> iterations are indicated in the dock of the test block.

### benchmark
Speed test runner
```bash
Usage:
  benchmark [<test>]

Arguments:
  test                  The name of the test case [Optional].
  
Options:
  -t, --times[=TIMES]   Number of iterations for all
  -l, --ls              Show list of tests  
```
> You can specify the class name as a test name, 
> or specify the name of the class together with the "@" method.

## Example
Show list my benchmarks:
```bash
php artisan benchmark -l
```
Output:
```bash
+-------------------------+------------------------------+-----------------------+-------+
| Class                   | Test                         | Description           | Times |
+-------------------------+------------------------------+-----------------------+-------+
| Tests\Benchmark\Bcrypt  | bcrypt@speed1                | Bcrypt tester         | 10    |
| Tests\Benchmark\Bcrypt  | bcrypt@speed2                | Double bcrypt tester  | 5     |
| Tests\Benchmark\Bcrypt2 | bcrypt2@speed1               | Bcrypt tester         | 10    |
| Tests\Benchmark\Bcrypt2 | bcrypt2@speed2               | Bcrypt tester         | 10    |
| Tests\Benchmark\Bcrypt2 | bcrypt2@speed3               | Bcrypt tester         | 10    |
| Tests\Benchmark\Bcrypt2 | bcrypt2@speed4               | Bcrypt tester         | 10    |
| Tests\Benchmark\Bcrypt2 | bcrypt2@speed5               | Bcrypt tester         | 10    |
| Tests\Benchmark\Bcrypt2 | bcrypt2@speed6               | Bcrypt tester         | 10    |
| Tests\Benchmark\Bcrypt2 | bcrypt2@speed7               | Bcrypt tester         | 10    |
| Tests\Benchmark\Text    | text@file_lines_get_contents | Text Speed 1          | 1000  |
| Tests\Benchmark\Text    | text@lang_in_text            | lang_in_text          | 1000  |
| Tests\Benchmark\Text    | text@tag_replace             | tag_replace           | 1000  |
| Tests\Benchmark\Text    | text@assoc                   | Assoc                 | 1000  |
| Tests\Benchmark\Text    | text@array_dots_uncollapse   | array_dots_uncollapse | 1000  |
| Tests\Benchmark\XsTest  | xs_test@speed1               | My test               | 1000  |
+-------------------------+------------------------------+-----------------------+-------+
```
I have benchmark `Text`:
```bash
php artisan benchmark text
```
Output:
```bash
Text Speed 1
 1000/1000 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%  1 sec/1 sec  26.0 MiB 

lang_in_text
 1000/1000 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100% < 1 sec/< 1 sec 26.0 MiB 

tag_replace
 1000/1000 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%  1 sec/1 sec  26.0 MiB 

Assoc
 1000/1000 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100% < 1 sec/< 1 sec 26.0 MiB 

array_dots_uncollapse
 1000/1000 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100% < 1 sec/< 1 sec 26.0 MiB 


+------------+--------------------+-------------------+----------------+-----------------+--------------+----------------------------+
| Test count | Total test seconds | Total test memory | Usage memory   | Free memory     | Memory limit | Current \ Total used % CPU |
+------------+--------------------+-------------------+----------------+-----------------+--------------+----------------------------+
| 5          | 1.3933 sec         | 47.3203125 kb     | 24.48337555 mb | 103.51662445 mb | 128 mb       | 2.01904296875 \ 0          |
+------------+--------------------+-------------------+----------------+-----------------+--------------+----------------------------+
+------------------------------+-----------------------+-------+-----------------------+-------------------------------+------------+
| Test                         | Description           | Times | In work               | Used memory                   | Used % CPU |
+------------------------------+-----------------------+-------+-----------------------+-------------------------------+------------+
| text@file_lines_get_contents | Text Speed 1          | 1000  | 0.2247 | 0.000225 sec | 1.078125 kb   | 0.00107813 kb | 0          |
| text@lang_in_text            | lang_in_text          | 1000  | 0.5282 | 0.000528 sec | 24.5 kb       |     0.0245 kb | 0          |
| text@tag_replace             | tag_replace           | 1000  | 0.337  | 0.000337 sec | 21.7421875 kb | 0.02174219 kb | 0          |
| text@assoc                   | Assoc                 | 1000  | 0.153  | 0.000153 sec | 0.0 b         |         0.0 b | 0          |
| text@array_dots_uncollapse   | array_dots_uncollapse | 1000  | 0.1504 |  0.00015 sec | 0.0 b         |         0.0 b | 0          |
+------------------------------+-----------------------+-------+-----------------------+-------------------------------+------------+
```
Or I need to check `text@assoc`, and I want call this bench 1 time:
```bash
php artisan benchmark text@assoc -t 1
```
Output:
```bash
Assoc
 1/1 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100% < 1 sec/< 1 sec 26.0 MiB 


+------------+--------------------+-------------------+----------------+-----------------+--------------+----------------------------+
| Test count | Total test seconds | Total test memory | Usage memory   | Free memory     | Memory limit | Current \ Total used % CPU |
+------------+--------------------+-------------------+----------------+-----------------+--------------+----------------------------+
| 1          | 0.0022 sec         | 1.078125 kb       | 24.42333984 mb | 103.57666016 mb | 128 mb       | 2.41650390625 \ 0          |
+------------+--------------------+-------------------+----------------+-----------------+--------------+----------------------------+
+------------+-------------+-------+-----------------------+---------------------------+------------+
| Test       | Description | Times | In work               | Used memory               | Used % CPU |
+------------+-------------+-------+-----------------------+---------------------------+------------+
| text@assoc | Assoc       | 1     | 0.0022 | 0.002238 sec | 1.078125 kb | 1.078125 kb | 0          |
+------------+-------------+-------+-----------------------+---------------------------+------------+
```

# Bench points
To start the server, use the command:
```bash
php artisan speed:watcher
```
And all you have left is to put "bpoint" in the order in 
which you need in the code, for example in controllers:
```php
class UserController extends Controller
{
    ...
    
    #[Get('/auth')]
    public function index(Request $request)
    {
        bpoint('Auth start');

        \Auth::loginUsingId(1, true);

        bpoint('Update user');

        \Auth::user()->touch();

        bpoint('Before return');
        
        return 'ok';
    }
    
    ...
}
```
And in the server console you will see:
```bash
 ---------- ----------------------------------------- 
  File       app/Http/Controllers/HomeController.php  
  Time       21:10:35.304829                          
  Message    Auth start                               
  Diff sec   0                                        
 ---------- ----------------------------------------- 

 ---------- ----------------------------------------- 
  File       app/Http/Controllers/HomeController.php  
  Time       21:10:35.310332                          
  Message    Update user                              
  Diff sec   0.0055                                   
 ---------- ----------------------------------------- 

 ---------- ----------------------------------------- 
  File       app/Http/Controllers/HomeController.php  
  Time       21:10:35.327846                          
  Message    Before return                            
  Diff sec   0.0175                                   
 ---------- ----------------------------------------- 

https://example.dev/auth
```
