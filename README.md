[![Latest Stable Version](https://poser.pugx.org/bingo-soft/mybatis/v/stable.png)](https://packagist.org/packages/bingo-soft/mybatis)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)](https://php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bingo-soft/mybatis/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/bingo-soft/mybatis/?branch=main)

# About

MyBatis SQL Mapper Framework ported to PHP

# Installation

Install MyBatis for PHP, using Composer:

```
composer require bingo-soft/mybatis
```

# Prerequisites for running tests

At current stage MyBatis is tested against Postgresql and MySQL databases. To run the tests you need to create these databases first:

```
- aname
- arrayresulttype
- automapping
- blog
- ibtest
- includes
- ognl_enum
- ognlstatic
- unmapped  
```

By default tests are configured to run against MySQL database. So, if you need to test it on Postgresql, please, change all configurations files.

# Running tests

```
./vendor/bin/phpunit ./tests
```

## Acknowledgements

This library is a port of Java [MyBatis](https://github.com/mybatis/mybatis-3).
