=== DocRaptor for Pressbooks ===

Version: 2.2.1
Stable tag: 2.2.1
Tags: ebooks, publishing
Requires PHP: 7.0
Requires at least: 4.9.4
Tested up to: 4.9.4
Pressbooks tested up to: 5.0.2
License: GPLv2
License URI: https://raw.githubusercontent.com/pressbooks/pressbooks-docraptor/master/LICENSE.md

This plugin implements a DocRaptor export module for Pressbooks.


== Description ==
[![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks-docraptor.svg?style=flat-square)](https://packagist.org/packages/pressbooks/pressbooks-docraptor) [![GitHub release](https://img.shields.io/github/release/pressbooks/pressbooks-docraptor.svg?style=flat-square)](https://github.com/pressbooks/pressbooks-docraptor/releases) [![Travis](https://img.shields.io/travis/pressbooks/pressbooks-docraptor.svg?style=flat-square)](https://travis-ci.org/pressbooks/pressbooks-docraptor/) [![Codecov](https://img.shields.io/codecov/c/github/pressbooks/pressbooks-docraptor.svg?style=flat-square)](https://codecov.io/gh/pressbooks/pressbooks-docraptor)

This plugin implements a [DocRaptor](https://docraptor.com/) export module for [Pressbooks](https://pressbooks.org), acting as a drop-in replacement for Pressbooks' [Prince](https://princexml.com) exporter.

== Installation ==

= Requirements =

* PHP >= 7.0
* Pressbooks >= 5.0.0
* WordPress >= 4.9.1

= Installing =

To install via [Composer](https://getcomposer.org) (recommended):

```composer require pressbooks/pressbooks-docraptor```

Or [manually install](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation) the [latest release](http://github.com/pressbooks/pressbooks-docraptor/releases/latest).

Then, set the constant `DOCRAPTOR_API_KEY` to your [API key](https://docraptor.com/documentation/api#api_authentication) for authenticated usage:

```define(\'DOCRAPTOR_API_KEY\', \'YOUR_API_KEY\');```

Set the constant `WP_ENV` to `staging` or `production` to disable [test mode](https://docraptor.com/documentation/api#api_test_docs):

```define(\'WP_ENV\', \'production\');```

== Upgrade Notice ==
== 2.2.1 ==

DocRaptor for Pressbooks 2.2.1 requires Pressbooks 5.0 and PHP >= 7.0.

== Changelog ==
= 2.2.1=

**Patches**

- Updated license: [bc113bb](https://github.com/pressbooks/pressbooks-docraptor/commit/bc113bb)
- Update Pressbooks tested up to version: [bc113bb](https://github.com/pressbooks/pressbooks-docraptor/commit/bc113bb)

= 2.2 =
**NOTICE:** Version 2.2 requires Pressbooks 5.0 and PHP >= 7.0.

* **[CORE ENHANCEMENT]** Updated several dependencies to their latest versions.

= 2.1 =
* **[CORE ENHANCEMENT]** The DocRaptor for Pressbooks plugin is now self-updating — GitHub Updater is no longer required (see #19, #20, and #21).

= 2.0.1 =
* Only fetch DocRaptor log when required (#14).

= 2.0.0 =
* Human Made Coding standards, other improvements.

= 1.2.0 =
* Changed to network-only plugin.
* Added local development mode.
* Improved the retrieval of PDFs.
* Prepared for localization.

= 1.1.0 =

* Test mode is now used within development environments only.

= 1.0.0 =

* 🚀 INITIAL RELEASE! 🚀
