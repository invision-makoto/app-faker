<?php

namespace IPS\faker\Content\Forum;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Class _Topic
 * @package IPS\faker\Content\Forum
 */
class _Topic extends \IPS\forums\Topic
{
	/**
	 * @param	\IPS\Node\Model	$forum	The forum container
	 * @param	array			$values	Generator form values
	 *
	 * @return	\IPS\faker\Content\Forum\Topic
	 */
	public static function create( \IPS\Node\Model $forum, array $values )
	{
		$generator = new \IPS\faker\Content\Generator();
		$tagsContainer = $values['add_tags'] ? $generator->tags() : array( 'tags' => null, 'prefix' => null );

		/* Generate the author */
		if ( $values['author'] )
		{
			$member = $values['member'];
		}
		elseif ( $values['author_type'] == 'random_fake' )
		{
			$member = $generator->fakeMember();
		}
		else
		{
			$member = $generator->guest();
		}

		/* Assign topic values */
		$topicValues = array(
			'faker_fake'		=> 1,
			'topic_title'		=> $generator->title(),
			'topic_content'		=> $generator->comment(),
			'topic_tags'		=> $tagsContainer['tags'],
			'topic_tags_prefix'	=> $tagsContainer['prefix']
		);

		if ( $values['after_posting'] )
		{
			if ( in_array( 'lock', $values['after_posting'] ) )
			{
				$topicValues['state'] = 'closed';
			}

			if ( in_array( 'hide', $values['after_posting'] ) )
			{
				$topicValues['approved'] = -1;
			}

			if ( in_array( 'pin', $values['after_posting'] ) )
			{
				$topicValues['pinned'] = 1;
			}

			if ( in_array( 'feature', $values['after_posting'] ) )
			{
				$topicValues['featured'] = 1;
			}
		}

		/* Create and save the topic */
		$obj = static::createItem( $member, $ipAddress = $generator->ipAddress(), new \IPS\DateTime, $forum );
		$obj->processForm( $topicValues );
		$obj->save();

		/* Create and save the first post in the topic */
		$commentClass = static::$commentClass;
		$comment = $commentClass::create( $obj, $topicValues[ 'topic_content' ], TRUE, ( !$member->name ) ? NULL : $member->name, $obj->hidden() ? FALSE : NULL, $member );
		$comment->ip_address = $ipAddress;
		$comment->save();

		$commentIdColumn = $commentClass::$databaseColumnId;
		call_user_func_array( array( 'IPS\File', 'claimAttachments' ), array_merge( array( 'newContentItem-' . static::$application . '/' . static::$module  . '-' . ( $forum ? $forum->_id : 0 ) ), $comment->attachmentIds() ) );

		if ( isset( static::$databaseColumnMap['first_comment_id'] ) )
		{
			$firstCommentIdColumn = static::$databaseColumnMap['first_comment_id'];
			$obj->$firstCommentIdColumn = $comment->$commentIdColumn;
			$obj->save();
		}

		return $obj;
	}
}