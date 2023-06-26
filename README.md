[![Build Status](https://github.com/MetaModels/attribute_levenshtein/actions/workflows/diagnostics.yml/badge.svg)](https://github.com/MetaModels/attribute_levenshtein/actions)
[![Latest Version tagged](http://img.shields.io/github/tag/MetaModels/attribute_levenshtein.svg)](https://github.com/MetaModels/attribute_levenshtein/tags)
[![Latest Version on Packagist](http://img.shields.io/packagist/v/MetaModels/attribute_levenshtein.svg)](https://packagist.org/packages/MetaModels/attribute_levenshtein)
[![Installations via composer per month](http://img.shields.io/packagist/dm/MetaModels/attribute_levenshtein.svg)](https://packagist.org/packages/MetaModels/attribute_levenshtein)

Levenshtein-based search
========================

The levenshtein attribute maintains an index of keywords across other attributes which can be searched using the 
levenshtein algorithm.

There is a filter rule that enables a similarity search via the created index. Optionally, an auto-completion
("Vanilla Script") can be activated (please note the template selection).

Adjustment of the index table
-----------------------------

The fields for storing the index can be enlarged as required e.g. from a length of `64` to `256`.
To do this, create a corresponding DCA file and adjust the values. (Note: the keys and the file name still
have the old, wrong notation with "sth").

```php
// contao/dca/tl_metamodel_levensthein_index.php
$GLOBALS['TL_DCA']['tl_metamodel_levensthein_index']['fields']['transliterated']['sql'] =
    'varbinary(256) NOT NULL default \'\'';
$GLOBALS['TL_DCA']['tl_metamodel_levensthein_index']['fields']['word']['sql']           =
    'varchar(256) COLLATE utf8_bin NOT NULL default \'\'';
```
