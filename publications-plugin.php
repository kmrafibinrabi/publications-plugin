<?php
/*
Plugin Name: Publications Plugin
Description: A plugin to create a custom post type for Publications and display them as cards.
Version: 1.0
Author: K M Rafi Bin Rabi
*/

function create_publications_post_type() {
    $labels = array(
        'name'                  => _x( 'Publications', 'Post type general name', 'textdomain' ),
        'singular_name'         => _x( 'Publication', 'Post type singular name', 'textdomain' ),
        'menu_name'             => _x( 'Publications', 'Admin Menu text', 'textdomain' ),
        'name_admin_bar'        => _x( 'Publication', 'Add New on Toolbar', 'textdomain' ),
        'add_new'               => __( 'Add New', 'textdomain' ),
        'add_new_item'          => __( 'Add New Publication', 'textdomain' ),
        'new_item'              => __( 'New Publication', 'textdomain' ),
        'edit_item'             => __( 'Edit Publication', 'textdomain' ),
        'view_item'             => __( 'View Publication', 'textdomain' ),
        'all_items'             => __( 'All Publications', 'textdomain' ),
        'search_items'          => __( 'Search Publications', 'textdomain' ),
        'not_found'             => __( 'No publications found.', 'textdomain' ),
        'not_found_in_trash'    => __( 'No publications found in Trash.', 'textdomain' ),
        'featured_image'        => _x( 'Publication Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'publication' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'thumbnail' ),
    );

    register_post_type( 'publication', $args );
}
add_action( 'init', 'create_publications_post_type' );

function add_publication_meta_boxes() {
    add_meta_box(
        'publication_url',
        __( 'Publication URL', 'textdomain' ),
        'render_publication_url_meta_box',
        'publication',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'add_publication_meta_boxes' );

function render_publication_url_meta_box( $post ) {
    wp_nonce_field( basename( __FILE__ ), 'publication_url_nonce' );
    $stored_meta = get_post_meta( $post->ID );
    ?>
<p>
    <label for="publication-url" class="row-title"><?php _e( 'URL', 'textdomain' ) ?></label>
    <input type="text" name="publication-url" id="publication-url"
        value="<?php if ( isset( $stored_meta['publication-url'] ) ) echo $stored_meta['publication-url'][0]; ?>" />
</p>
<?php
}

function save_publication_meta( $post_id ) {
    // Check nonce
    if ( !isset( $_POST['publication_url_nonce'] ) || !wp_verify_nonce( $_POST['publication_url_nonce'], basename( __FILE__ ) ) ) {
        return $post_id;
    }
    // Check autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
    }
    // Check permissions
    if ( 'publication' === $_POST['post_type'] && !current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
    }

    // Update URL
    if ( isset( $_POST['publication-url'] ) ) {
        update_post_meta( $post_id, 'publication-url', sanitize_text_field( $_POST['publication-url'] ) );
    }
}
add_action( 'save_post', 'save_publication_meta' );

function publications_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'posts_per_page' => 10,
    ), $atts, 'publications' );

    $query = new WP_Query( array(
        'post_type'      => 'publication',
        'posts_per_page' => $atts['posts_per_page'],
    ) );

    $output = '<div class="publications-list">';

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $url = get_post_meta( get_the_ID(), 'publication-url', true );
            $output .= '<div class="card">';
            if ( has_post_thumbnail() ) {
                $output .= '<img src="' . get_the_post_thumbnail_url( get_the_ID(), 'full' ) . '" alt="' . get_the_title() . '" style="width:100%">';
            }
            $output .= '<div class="container">';
            if ( $url ) {
                $output .= '<h4><b><a href="' . esc_url( $url ) . '" target="_blank">' . get_the_title() . '</a></b></h4>';
            } else {
                $output .= '<h4><b>' . get_the_title() . '</b></h4>';
            }
            $output .= '</div></div>';
        }
    } else {
        $output .= '<p>No publications found.</p>';
    }

    $output .= '</div>';

    wp_reset_postdata();

    return $output;
}
add_shortcode( 'publications', 'publications_shortcode' );

function publications_styles() {
    echo '
    <style>
    .card {
        box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
        transition: 0.3s;
        width: 30%;
        margin-bottom: 20px;
    }
    .card:hover {
        box-shadow: 0 8px 16px 0 rgba(0,0,0,0.2);
    }
    .container {
        padding: 2px 16px;
    }
    .publications-list {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }
    .container a {
        text-decoration: none;
        color: inherit;
    }
    .container a:hover {
        text-decoration: underline;
    }
    </style>
    ';
}
add_action( 'wp_head', 'publications_styles' );