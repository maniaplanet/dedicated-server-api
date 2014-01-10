ManiaLive
=========

ManiaLive is a *simple, modular, high-performance PHP manager for the Maniaplanet Dedicated Server*. It is developed and supported by Ubisoft Nadeo.

It has been design to be as *simple* as possible to use and to configure. You can already take advantage of the *numerous plugins written by the community*. 

ManiaLive is also *developer-friendly* with a *powerful yet simple plugin system*. The internals feature some advanced technologies such as *multi-threading* (very useful for long tasks -think MySQL or HTTP requests- to be non-blocking) or a *GUI toolkit* based on ManiaLib (no XML to write, everything is written in pure object-oriented PHP).

There's also a version of ManiaLive for TrackMania Forever.

Requirements
------------

  * A running Maniaplanet Dedicated Server with access to its XML-RPC interface
  * PHP 5.3.1 or newer, [CLI](http://php.net/manual/en/features.commandline.php)
  * [cURL extension](http://php.net/manual/en/book.curl.php)
  * (optional) [SQLlite extension](http://fr.php.net/manual/en/book.sqlite.php) in order to use the multi-threading feature
