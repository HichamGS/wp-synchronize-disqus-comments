# wp-synchronize-disqus-comments

Simple, Using minimum code to configure disqus in wordpress with + a way to synchronise disqus comments with wp comments.

## prerequisite

- First configure a [Disqus](https://www.disqus.com) application to have the following credentials :

	* DISQUS_SHORTNAME
	* DISQUS_API_SECRET

- Then Copy both files comments-disqus.php and disqus in the root path of your theme.

- then you set your credentials in the file comments-disqus.php (lines 7 and 8 ):

	define( DISQUS_SHORTNAME', '');
	define('DISQUS_API_SECRET', '' );

- finaly to make it work, you have to activate disqus, using the hook :

	you copy the lines of code in your file functions.php

```
add_filter('comments_template', 'disqus_comments_template');
function disqus_comments_template($value) {
	return locate_template('comments_disqus.php');
}
```