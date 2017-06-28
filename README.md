# DocRaptor for Pressbooks #
**Tags:** ebooks, publishing  
**Requires at least:** 4.8.0  
**Tested up to:** 4.8.0  
**Stable tag:** 1.2.0  
**License:** GPLv2  
**License URI:** https://raw.githubusercontent.com/pressbooks/pressbooks-docraptor/master/LICENSE.md  

This plugin implements a DocRaptor export module for Pressbooks.


## Description ##
[![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks-docraptor.svg?style=flat-square)](https://packagist.org/packages/pressbooks/pressbooks-docraptor) [![GitHub release](https://img.shields.io/github/release/pressbooks/pressbooks-docraptor.svg?style=flat-square)](https://github.com/pressbooks/pressbooks-docraptor/releases) [![Travis](https://img.shields.io/travis/pressbooks/pressbooks-docraptor.svg?style=flat-square)](https://travis-ci.org/pressbooks/pressbooks-docraptor/) [![Codecov](https://img.shields.io/codecov/c/github/pressbooks/pressbooks-docraptor.svg?style=flat-square)](https://codecov.io/gh/pressbooks/pressbooks-docraptor)

This plugin implements a [DocRaptor](https://docraptor.com/) export module for [Pressbooks](https://pressbooks.org), acting as a drop-in replacement for Pressbooks' [Prince](https://princexml.com) exporter.

## Installation ##

### Requirements ###

* PHP >= 5.6
* Pressbooks >= 4.0.0
* WordPress >= 4.8.0

### Installing ###

To install via [Composer](https://getcomposer.org) (recommended):

```composer require pressbooks/pressbooks-docraptor```

Or [manually install](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation) the [latest release](http://github.com/pressbooks/pressbooks-docraptor/releases/latest).

Then, set the constant `DOCRAPTOR_API_KEY` to your [API key](https://docraptor.com/documentation/api#api_authentication) for authenticated usage:

```define(\'DOCRAPTOR_API_KEY\', \'YOUR_API_KEY\');```

Set the constant `WP_ENV` to `staging` or `production` to disable [test mode](https://docraptor.com/documentation/api#api_test_docs):

```define(\'WP_ENV\', \'production\');```

### Updating ###

DocRaptor for Pressbooks supports [Github Updater](https://github.com/afragen/github-updater).

## Changelog ##

### 2.0.0 ###
* Human Made coding standards
* Refactoring 

### 1.2.0 ###
* Changed to network-only plugin.
* Added local development mode.
* Improved the retrieval of PDFs.
* Prepared for localization.

### 1.1.0 ###

* Test mode is now used within development environments only.

### 1.0.0 ###

* 🚀 INITIAL RELEASE! 🚀
