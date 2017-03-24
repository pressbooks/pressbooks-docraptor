# Docraptor for Pressbooks

[![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks-docraptor.svg?style=flat-square)](https://packagist.org/packages/pressbooks/pressbooks-docraptor) [![GitHub release](https://img.shields.io/github/release/pressbooks/pressbooks-docraptor.svg?style=flat-square)](https://github.com/pressbooks/pressbooks-docraptor/releases)

This plugin implements a [Docraptor](https://docraptor.com/) export module for Pressbooks.

## Requirements

* PHP >= 5.6
* Pressbooks >= 3.9.8
* WordPress >= 4.7.3

## Usage

Set the constant `PB_DOCRAPTOR_API_KEY` to your [API key](https://docraptor.com/documentation/api#api_authentication) for authenticated usage:

```define('PB_DOCRAPTOR_API_KEY', 'YOUR_API_KEY');```

Set the constant `WP_ENV` to `production` to disable [test mode](https://docraptor.com/documentation/api#api_test_docs):

```define('WP_ENV', 'production');```

## Updating

Docraptor for Pressbooks supports [Github Updater](https://github.com/afragen/github-updater).
