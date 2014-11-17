
# PHPTAL - Template Attribute Language for PHP

Master: [![Build Status](https://secure.travis-ci.org/pornel/PHPTAL.png?branch=master)](http://travis-ci.org/pornel/PHPTAL)

Usage requirements
==================

To use PHPTAL in your projects, you will only require PHP 5.1.2 or later.

If you want to use the builtin internationalisation system (I18N) the gettext extension must be compiled into PHP (`--with-gettext`).


Non-PEAR install
================
To run you only need PHPTAL.php and files in PHPTAL directory. Other files are for unit tests and PEAR installer.
You can either use Composer to get the files for you or download them directly.

Composer install
----------------
To add PHPTAL as a local, per-project dependency to your project, simply run the command `composer require phptal/phptal` from the root of your project. This will add a dependency for the latest PHPTAL version to your projects `compiser.json` file. 

Alternatively, add a dependency on `phptal/phptal` to your project's `composer.json` file manually and run 
Composer with the `build` or `update` command. 

Here is a minimal example of a `composer.json` file that defines a dependency on PHPTAL 1.3:

    {
        "require": {
            "phptal/phptal": "~1.3.0"
        }
    }
    
For a standalone (system-wide) installation via Composer, `composer require phptal/phptal --global` can be used. Alternatively, a composer.json similar to the one shown below can be used from an arbitary directory.

    {
        "name": "phptal",
        "description": "phptal",
        "require": {
            "phptal/phptal": "~1.3.0"
        },
        "config": {
            "bin-dir": "/path/where/to/install/phptal/"
        }
    }
    
Once you have [installed Composer][composer-install] you simply need to run the following command 
from the root of your project:

    php composer.phar install

or if you have Composer installed as a binary:

    composer install

Direct Download
---------------    
You can also get the latest PHPTAL package directly from [phptal.org][phptal].

    tar zxvf PHPTAL-X.X.X.tar.gz
    mv PHPTAL-X.X.X/PHPTAL* /path/to/your/php/include/path/


PEAR Install
============

Get the latest PHPTAL package from [phptal.org][phptal].

Then run:

    pear install PHPTAL-X.X.X.tar.gz



Getting the latest development version
======================================

You can checkout the latest development version using:

    svn co https://svn.motion-twin.com/phptal/trunk phptal

Or you can fork or clone it from [the PHPTAL git repository on Github][phptal-github].

PHPTAL development requirements
===============================

If you want to hack PHPTAL (don't forget to send me patches), you will require:

  - The PHPTAL development package
  - PEAR (to easily install other tools)
    http://pear.php.net

  - Phing to run maintainance tasks

        pear channel-discover pear.phing.info
        pear install phing/phing

  - PHPUnit 3.4 to run tests

        pear channel-discover pear.phpunit.de
		pear channel-discover pear.symfony-project.com
		pear channel-discover components.ez.no
        pear install phpunit/PHPUnit
        
Installing development requirements with Composer
--------------------------------------------------
All the development dependencies can be installed with Composer by running the
install command (or the update command, if you've already got a stable version 
through Composer) from the root of your project directory.


[phptal]: http://phptal.org/
[composer-install]: http://getcomposer.org/download/
[phptal-github]: https://github.com/pornel/PHPTAL
