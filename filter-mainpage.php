<?php
add_filter('the_content', 'filter_mainpage');

function filter_mainpage($content)
{
    if (is_front_page() ) {
        $post = get_post();

        return $post->ID . $content;
    } else
        return $content;
}