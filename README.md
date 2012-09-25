envis.walkner.pl
================

What was supposed to be a simple PHP & MySQL database,
grew bigger and bigger over the years without any major rewrites.

It's main purpose is to allow my father to keep track of his company's projects.

Code is in English, text is in Polish.

## Requirements

* An HTTP server with PHP 5.3+ and MySQL 5+.
* A directory accessible through the HTTP containing:
  * `fff` directory with [famfamfam Silk Icons Evolved](http://code.google.com/p/famfamfam/)
  * `jquery-ui/1.8.23` directory with [jQuery UI 1.8.23](http://jqueryui.com/)
  * `jquery/1.8.1` directory with [jQuery 1.8.1](http://jquery.com/)
  * `jquery-plugins` directory containing:
      * `uploadify/2.0.3` directory with [Uploadify 2.0.3](http://www.uploadify.com/)
      * `colorpicker/2009.05.23` directory with [Colorpicker 2009.05.23](http://www.eyecon.ro/colorpicker/)
      * `simplemodal/1.3` directory with [SimpleModal 1.3](http://www.ericmmartin.com/projects/simplemodal/)
      * `conmenu/1.0.1` directory with [ConMenu 1.0.1](http://archive.plugins.jquery.com/project/conmenu)
      * `autoresize/0.1` directory with [autoresize 0.1](http://cdn1.walkner.pl/jquery-plugins/autoresize/jquery.autoresize-0.1.zip)
      * `lightbox/2.51` directory with [LightBox 2.51](http://lokeshdhakar.com/projects/lightbox2/)
      * `hotkeys/0.8` directory with [hotkeys 0.8](http://www.openjs.com/scripts/events/keyboard_shortcuts/)
      * `jstree/0.9.8` directory with [jsTree 0.9.8](http://www.jstree.com/)
      * `jstree/1.0-rc1` directory with [jsTree 1.0-rc1](http://www.jstree.com/)
      * `inview/1.0.0` directory with [inview 1.0.0](https://github.com/protonet/jquery.inview)
      * `tmpl/1.0.0beta1` directory with [tmpl 1.0.0beta1](http://api.jquery.com/category/plugins/templates/)
      * `scrollTo/1.4.3.1` directory with [scrollTo 1.4.3.1](http://flesler.blogspot.com/2007/10/jqueryscrollto.html)
  * `ckeditor/3.0` directory with [CKEditor 3.0](http://ckeditor.com/)
  * `ckeditor/3.6.2` directory with [CKEditor 3.6.2](http://ckeditor.com/)
  * `ckeditor/4.0beta` directory with [CKEditor 4.0 beta](http://ckeditor.com/)

## Installation

1. Grant write permissions to `_files_/` directory and everything in in.
2. Create a new MySQL database and import the `structure.sql` file into it.
3. Create a super user:

   ```sql
   INSERT INTO users SET
      super=1,
      email='samone@the.net',
      password='8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92',
      name='Sam One',
      createdAt=NOW()
   ```

4. Change the db connection and website path settings in the `config.php` file.
5. Log in using `samone@the.net`/`123456`.

## License

[New BSD License](http://opensource.org/licenses/BSD-3-Clause)
