<?php
/*
Plugin Name: Custom Chatbot for Q&A
Description: A plugin to manage chatbot questions and answers.
Version: 1.0
Author: Awais Y.
*/

function react_chatbot_enqueue_scripts() {
    wp_enqueue_script(
        'react-chatbot-script',
        plugins_url('assets/index-D02pozkG.js', __FILE__), // Adjust the path based on your build
        array(),
        null,
        true
    );
    wp_enqueue_style(
        'react-chatbot-style',
        plugins_url('assets/index-UyhBv1Or.css', __FILE__) // Adjust the path based on your build
    );
}

add_action('wp_enqueue_scripts', 'react_chatbot_enqueue_scripts');

function react_chatbot_render() {
    return '<div class="awais-chatbot-container"><div id="root"></div></div>';
}


add_shortcode('react_chatbot', 'react_chatbot_render');
