<?php
/**
 * @author hicham.ajarif
 * synchronize disqus comments with WP
 */
// SET Disqus credentials
define('DISQUS_SHORTNAME', '' );
define('DISQUS_API_SECRET', '' );
define('DISQUS_COMMENTS_LIMIT', 100 );

define('DISQUS_LAZY_LOADING', true );
define('SYNC_DISQUS_COMMENTS', true );

require_once dirname(__FILE__).'/disqus.php';

$post_id = get_the_ID();
$page_disqus_active = false;

//page include disqus comments
if( is_page() && get_post_meta( $post_id, 'disqus_page_comments', true) ){
	$page_disqus_active = true;
	//get page disqus identifier
	$identifier  = DISQUS_SHORTNAME . '_' . get_query_var('pagename') . '_' . $post_id;
}else{
	$identifier  = DISQUS_SHORTNAME.'_article_'.$post_id;
}

?>
<div id="comments" class="comments-area col-xs-12 pull-left">
	<span id="comment_count" style="display: none;" class='disqus-comment-count' data-disqus-identifier='<?php echo $identifier ?>'></span>

	<div id="disqus_thread"></div>
	<script type="text/javascript">
		<?php if( !$page_disqus_active ){ ?>
			function disqus_config() {

				this.callbacks.onReady = [function() {
					var frame = document.getElementById('disqus_thread').getElementsByTagName('iframe');
					var meta_refresh = document.getElementById("meta-refresh");
					var $refresh_duration = 300;

					if (site_config_js.refresh_meta_duration) {
						$refresh_duration = site_config_js.refresh_meta_duration;
					}

					window.setInterval(function(){
						if(document.activeElement == frame[0]) {
							site_config_js.enable_refresh_meta = false;

							if (document.head.contains(meta_refresh)) {
								document.head.removeChild(meta_refresh);
							} 
						} else {
							site_config_js.enable_refresh_meta = true;

							if (!document.head.contains(meta_refresh)) {
								meta_refresh = document.createElement('meta'); // is a node
								meta_refresh.httpEquiv = "refresh";
								meta_refresh.content = $refresh_duration;
								meta_refresh.id = "meta-refresh";
								document.head.appendChild(meta_refresh);
							}
						}
					}, 1000);
				}];

				this.callbacks.onNewComment = [function(comment) {
					var post_id = "<?php echo $post_id; ?>";
					$ = jQuery ;
					$.post( "/?sync_new_comments", { post_id: post_id, comment_id: comment.id })
					.done(function(data) {
						console.log( "comment imported successfully !" );
					})
					.fail(function() {
						console.log( "error !" );
					})
					location.reload();
				}];
			}
		<?php } ?>

		/* * * CONFIGURATION VARIABLES: EDIT BEFORE PASTING INTO YOUR WEBPAGE * * */
	        var disqus_shortname = "<?php echo DISQUS_SHORTNAME; ?>"; // required: replace example with your forum shortname
			//var disqus_url = 'http://[% $smarty.server.HTTP_HOST%][% $smarty.server.REQUEST_URI %]';
			var disqus_identifier = "<?php echo $identifier; ?>";

			/* * * DON'T EDIT BELOW THIS LINE * * */
			<?php if (defined('DISQUS_LAZY_LOADING') && DISQUS_LAZY_LOADING==true){ ?>

				function load_disqus_embed(){
					(function() {
						var dsq = document.createElement('script'); dsq.type = 'text/javascript';
						dsq.async = true;
						dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
						(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
					})();
				}

				var $disqus_container_id = jQuery('#disqus_thread');
				var disqus_embed_loaded = false ;
				var $window = jQuery(window) ;
				jQuery(document).scroll(function(){
					if( disqus_embed_loaded == false && $window.scrollTop() + $window.height()  > $disqus_container_id.offset().top ){
						disqus_embed_loaded = true;
						load_disqus_embed() ;
					}
				});


			<?php }else{ ?>
				(function() {
					var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
					dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
					(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
				})();
			<?php } ?>

	</script>
	<noscript>
		<?php include( locate_template('comments.php') ); ?>
	</noscript>
</div>