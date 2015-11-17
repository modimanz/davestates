<?php

// Security
// Block direct access to the plugin
defined( 'ABSPATH' ) or die( 'Action not allowed bub.' );

/**
 * The template for displaying all statemaps
 *
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <?php
        // Start the loop.
        while ( have_posts() ) : the_post();

            /*
             * Include the post format-specific template for the content. If you want to
             * use this in a child theme, then include a file called called content-___.php
             * (where ___ is the post format) and that will be used instead.
             */
            $statecode = "ca";
            $postid = "poop";
            //echo do_shortcode("[davestates-statemap]");

            get_template_part( 'content', get_post_format() );

            //echo do_shortcode("[davestates-statemap-statedata postid=\"".$postid."\" statecode=\"".$statecode."\"]");
            // If comments are open or we have at least one comment, load up the comment template.
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;


            /*
            // Previous/next post navigation.
            the_post_navigation( array(
                'next_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Next', 'twentyfifteen' ) . '</span>' .
                    '<span class="screen-reader-text">' . __( 'Next post:', 'twentyfifteen' ) . '</span>' .
                    '<span class="post-title">%title</span>',
                'prev_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Previous', 'twentyfifteen' ) . '</span> ' .
                    '<span class="screen-reader-text">' . __( 'Previous post:', 'twentyfifteen' ) . '</span>' .
                    '<span class="post-title">%title</span>',
            ) );

            */

            // End the loop.
        endwhile;
        ?>

    </main><!-- .site-main -->
</div><!-- .content-area -->

<?php get_footer(); ?>


<?php
/*
    <php get_header(); ?>

    <div id="content">

        <?php query_posts('post_type=post&post_status=publish&posts_per_page=10&paged='. get_query_var('paged')); ?>

        <?php if( have_posts() ): ?>

            <?php while( have_posts() ): the_post(); ?>

                <div id="post-<?php get_the_ID(); ?>" <?php post_class(); ?>>

                    <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( array(200,220) ); ?></a>

                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

                    <span class="meta"><?php author_profile_avatar_link(48); ?> <strong><?php the_time('F jS, Y'); ?></strong> / <strong><?php the_author_link(); ?></strong> / <span class="comments"><?php comments_popup_link(__('0 comments','example'),__('1 comment','example'),__('% comments','example')); ?></span></span>

                    <?php the_excerpt(__('Continue reading »','example')); ?>

                </div><!-- /#post-<?php get_the_ID(); ?> -->

            <?php endwhile; ?>

            <div class="navigation">
                <span class="newer"><?php previous_posts_link(__('« Newer','example')) ?></span> <span class="older"><?php next_posts_link(__('Older »','example')) ?></span>
            </div><!-- /.navigation -->

        <?php else: ?>

            <div id="post-404" class="noposts">

                <p><?php _e('None found.','example'); ?></p>

            </div><!-- /#post-404 -->

        <?php endif; wp_reset_query(); ?>

    </div><!-- /#content -->

<?php get_footer(); ?>
        */
