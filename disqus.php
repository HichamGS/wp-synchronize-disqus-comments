<?php
if( defined('SYNC_DISQUS_COMMENTS') && SYNC_DISQUS_COMMENTS==true ){
	function get_disqus_api_results($url){
		// Get the results
		$session = curl_init($url);
		$ch = curl_init();
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$data = curl_exec($session);
		curl_close($session);

		$result = json_decode($data);// decode the json data to make it easier to parse the php
		if ($result === NULL)//tester le resultat
			return false;
		return $result;
	}

	function import_comments($identifier, $post_id){
		global $wpdb;
		if( $wpdb->get_row($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'dsq_needs_sync' AND post_id = %s LIMIT 1", $post_id)) )
			return false;

		$api_secret = DISQUS_API_SECRET; // Disqus api secret key
		$forum = DISQUS_SHORTNAME; // Disqus shortname
		$endpoint = 'https://disqus.com/api/3.0/threads/listPosts.json?api_secret='.$api_secret.'&forum='.$forum.'&thread:ident='.$identifier.'&limit='.DISQUS_COMMENTS_LIMIT.'&order=asc';
		
		$result = get_disqus_api_results($endpoint);
		if( $result->code == 13 ) //code 13 means exceeded the rate limit for this resource
			return $result->code;
		else{
			add_post_meta( $post_id, 'dsq_needs_sync', '1', true );
			if( $result->code != 0 || !$result->response )
				return false;
		}

		foreach ( $result->response as $comment )
			import_commentdata($comment, $post_id);
		
		update_post_meta( $post_id, 'dsq_needs_sync', '0', true );
		return true;
	}

	function import_commentdata($comment, $post_id){
		global $wpdb;
		$found = $wpdb->get_row($wpdb->prepare( "SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = 'dsq_post_id' AND meta_value = %s LIMIT 1", $comment->id));

		if( !$found ) {
			$commentdata = array(
				'comment_post_ID' => $post_id,
				'comment_date' => $comment->createdAt,
				'comment_date_gmt' => $comment->createdAt,
				'comment_content' => apply_filters('pre_comment_content', $comment->message),
				'comment_approved' => $comment->isApproved,
				'comment_agent' => 'Disqus/1.1('.DISQUS_VERSION.'):'.intval($comment->id),
				'comment_type' => '',
			);

			if ($comment->parent) {
				$parent_id = $wpdb->get_var($wpdb->prepare( "SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = 'dsq_post_id' AND meta_value = %s LIMIT 1", $comment->parent));
				if ($parent_id) {
					$commentdata['comment_parent'] = $parent_id;
				}
			}

			if ($comment->is_anonymous) {
				$commentdata['comment_author'] = $comment->anonymous_author->name;
				$commentdata['comment_author_email'] = $comment->anonymous_author->email;
				$commentdata['comment_author_url'] = $comment->anonymous_author->profileUrl;
			} else {
				if (!empty($comment->author->name)) {
					$commentdata['comment_author'] = $comment->author->name;
				} else {
					$commentdata['comment_author'] = $comment->author->username;
				}
				$commentdata['comment_author_email'] = $comment->author->email;
				$commentdata['comment_author_url'] = $comment->author->profileUrl;
				$commentdata['user_id'] = $comment->author->id;
			}
			$commentdata['comment_author_IP'] = $comment->ip_address;


			$comment_id = wp_insert_comment($commentdata);
			add_comment_meta( $comment_id, 'dsq_post_id', $comment->id, true );

		}   
	}

	if( isset($_GET['sync_new_comments']) ){

		$commentId = $_POST['comment_id'];
		$endpoint =  'http://disqus.com/api/3.0/posts/details.json?api_secret='. DISQUS_API_SECRET .'&post=' . intval($commentId) . '&related=thread';

		$result = get_disqus_api_results($endpoint);

		if ( !$result || $result->code != 0 || !$result->response )  die('Import Failed !');

		import_commentdata($result->response, $_POST['post_id']);
		echo json_encode(array('success'=>'true', 'message'=>$commentId)); exit();
	}
}