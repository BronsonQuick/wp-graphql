<?php
namespace WPGraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQLRelay\Relay;
use WPGraphQL\Connections;
use WPGraphQL\Data\DataSource;
use WPGraphQL\Types;

class PostObjectType extends ObjectType {

	public function __construct( $post_type ) {

		$node_definition = DataSource::get_node_definition();
		$allowed_taxonomies  = \WPGraphQL::$allowed_taxonomies;
		$post_type_object = get_post_type_object( $post_type );
		$single_name      = $post_type_object->graphql_single_name;

		$config = [
			'name'         => $single_name,
			'description'  => sprintf( __( 'The %s object type', 'wp-graphql' ), $single_name ),
			'fields'       => function() use ( $single_name, $post_type_object, $allowed_taxonomies ) {
				$fields = [
					'id' => [
						'type' => Types::non_null( Types::id() ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ( ! empty( $post->post_type ) && ! empty( $post->ID ) ) ? Relay::toGlobalId( $post->post_type, $post->ID ) : null;
						},
					],
					$single_name . 'Id'              => array(
						'type'        => Types::int(),
						'description' => esc_html__( 'The id field matches the WP_Post->ID field.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->ID ) ? absint( $post->ID ) : null;
						},
					),
					'author'          => array(
						'type'        => Types::user(),
						'description' => esc_html__( "The author field will return a queryable User type matching the 
						post's author.", 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->post_author ) ? new \WP_User( $post->post_author ) : null;
						},
					),
					'date'            => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'Post publishing date.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->post_date ) ? $post->post_date : null;
						},
					),
					'dateGmt'        => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'The publishing date set in GMT.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->post_date_gmt ) ? $post->post_date_gmt : null;
						},
					),
					'content'         => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'The content of the post. This is currently just the raw content. 
						An amendment to support rendered content needs to be made.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->post_content ) ? apply_filters( 'the_content', $post->post_content ) : null;
						},
					),
					'title'           => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'The title of the post. This is currently just the raw title. An 
						amendment to support rendered title needs to be made.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->post_title ) ? $post->post_title : null;
						},
					),
					'excerpt'         => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'The excerpt of the post. This is currently just the raw excerpt. 
						An amendment to support rendered excerpts needs to be made.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							$excerpt = apply_filters( 'the_excerpt', apply_filters( 'get_the_excerpt', $post->post_excerpt, $post ) );
							return ! empty( $excerpt ) ? $excerpt : null;
						},
					),
					'status'     => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'The current status of the post. ( published, draft, etc. ) This 
						should be changed to an enum type supporting valid stati.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->post_status ) ? $post->post_status : null;
						},
					),
					'commentStatus'  => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'Whether the comments are open or closed for this particular post. 
						Needs investigating.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->comment_status ) ? $post->comment_status : null;
						},
					),
					'pingStatus'     => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'Whether the pings are open or closed for this particular post. 
						Needs investigating.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->ping_status ) ? $post->ping_status : null;
						},
					),
					'slug'            => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'The uri slug for the post. This is equivalent to the 
						WP_Post->post_name field and the post_name column in the database for the `post_objects` 
						table.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->post_name ) ? $post->post_name : null;
						},
					),
					'toPing'         => array(
						'type'        => Types::boolean(),
						'description' => esc_html__( 'URLs queued to be pinged.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->to_ping ) ? true : false;
						},
					),
					'pinged'          => array(
						'type'        => Types::boolean(),
						'description' => esc_html__( 'URLs that have been pinged.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->pinged ) ? true : false;
						},
					),
					'modified'        => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'The local modified time for a post. If a post was recently 
						updated the modified field will change to match the corresponding time.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->post_modified ) ? $post->post_modified : null;
						},
					),
					'modifiedGmt'    => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'The GMT modified time for a post. If a post was recently 
						updated the modified field will change to match the corresponding time in GMT.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->post_modified_gmt ) ? $post->post_modified_gmt : null;
						},
					),
					// @todo: add the parent
					'editLast'        => [
						'type'        => Types::string(),
						'description' => __( 'The ID of the user that most recently edited the object', 'wp-graphql' ),
						'resolve'     => function( \WP_Post $post, array $args, ResolveInfo $info ) {
							$edit_last = get_post_meta( $post->ID, '_edit_last', true );
							return ! empty( $edit_last ) ? absint( $edit_last ) : null;
						},
					],
					'editLock'        => [
						'type'        => Types::string(),
						'description' => __( 'String indicating the timestamp and ID of the user that most 
						recently edited an object. Can be used to determine if it can safely be 
						edited by another user.', 'wp-graphql' ),
						'resolve'     => function( \WP_Post $post, array $args, ResolveInfo $info ) {
							$edit_lock = get_post_meta( $post->ID, '_edit_lock', true );
							return ! empty( $edit_lock ) ? absint( $edit_lock ) : null;
						},
					],
					'enclosure'        => [
						'type'        => Types::string(),
						'description' => __( 'The RSS enclosure for the object', 'wp-graphql' ),
						'resolve'     => function( \WP_Post $post, array $args, $context, ResolveInfo $info ) {
							$enclosure = get_post_meta( $post->ID, 'enclosure', true );
							return ! empty( $enclosure ) ? $enclosure : null;
						},
					],
					'guid'            => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'The global unique identifier for this post. This currently 
						matches the value stored in WP_Post->guid and the guid column in the `post_objects` database 
						table.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->guid ) ? $post->guid : null;
						},
					),
					'menuOrder'      => array(
						'type'        => Types::int(),
						'description' => esc_html__( 'A field used for ordering posts. This is typically used with 
						nav menu items or for special ordering of hierarchical content types.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->menu_order ) ? absint( $post->menu_order ) : 0;
						},
					),
					'type'            => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'This field tells what kind of content type the object is. In 
						WordPress different post types are used to denote different types of content. This field is 
						equivalent to the value of WP_Post->post_type and the post_type column in the `post_objects` 
						database table.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->post_type ) ? $post->post_type : null;
						},
					),
					'mimeType'       => array(
						'type'        => Types::string(),
						'description' => esc_html__( 'If the post is an attachment or a media file, this field will 
						carry the corresponding MIME type. This field is equivalent to the value of 
						WP_Post->post_mime_type and the post_mime_type column in the `post_objects` database 
						table.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->post_mime_type ) ? $post->post_mime_type : null;
						},
					),
					'desiredSlug' => [
						'type' => Types::string(),
						'description' => esc_html__( 'The desired slug of the post', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							$desired_slug = get_post_meta( $post->ID, '_wp_desired_post_slug', true );
							return ! empty( $desired_slug ) ? $desired_slug : null;
						},
					],
					'link' => [
						'type' => Types::string(),
						'description' => esc_html__( 'The desired slug of the post', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							$link = get_permalink( $post->ID );
							return ! empty( $link ) ? $link : null;
						},
					],
				];
				
				if ( post_type_supports( $post_type_object->name, 'comments' ) ) {
					$fields['commentCount']  = [
						'type'        => Types::int(),
						'description' => esc_html__( 'The number of comments. Even though WP GraphQL denotes this 
						field as an integer, in WordPress this field should be saved as a numeric string for 
						compatability.', 'wp-graphql' ),
						'resolve' => function( \WP_Post $post, $args, $context, ResolveInfo $info ) {
							return ! empty( $post->comment_count ) ? absint( $post->comment_count ) : 0;
						},
					];
				}

				/**
				 * Add term connections based on the allowed taxonomies that are also
				 * registered to the post_type
				 */
				if ( ! empty( $allowed_taxonomies ) && is_array( $allowed_taxonomies ) ) {
					foreach ( $allowed_taxonomies as $taxonomy ) {
						// If the taxonomy is in the array of taxonomies registered to the post_type
						if ( in_array( $taxonomy, get_object_taxonomies( $post_type_object->name ), true ) ) {
							$tax_object                                 = get_taxonomy( $taxonomy );
							$fields[ $tax_object->graphql_plural_name ] = Connections::term_objects_connection( $tax_object );
						}
					}
				}
				$fields = apply_filters( 'graphql_post_object_type_fields_' . $single_name, $fields, $post_type_object );

				ksort( $fields );

				return $fields;
			},
			'interfaces' => [ $node_definition['nodeInterface'] ],
		];
		parent::__construct( $config );
	}
}
