<?php
/*
Plugin Name: Loom Chatbot
Description: A modern AI-powered chatbot for LoomVision using the Gemini API.
Version: 1.0
Author: Hussain Ali 
Company: Digital Boost 
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue scripts and styles
function loom_enqueue_scripts() {
    wp_enqueue_script('loom-chatbot-script', plugin_dir_url(__FILE__) . 'js/loom-chatbot-script.js', array('jquery'), null, true);
    wp_localize_script('loom-chatbot-script', 'loom_chatbot_obj', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('loom_chatbot_nonce')));
    wp_enqueue_style('loom-chatbot-styles', plugin_dir_url(__FILE__) . 'css/loom-chatbot-style.css');
}
add_action('wp_enqueue_scripts', 'loom_enqueue_scripts');

// Register the shortcode
function loom_chatbot_shortcode() {
    ob_start();
                      ?>
   <div class="loom-chatbot-container minimized">
     <div class="loom-chatbot-header">
        <div class="icon-container">
        <img src="<?php echo plugin_dir_url(__FILE__); ?>images/loom-icon.png" alt="Loom Icon">
        </div>
        <span class="header-title">Hussain's Bot</span>
        <div class="online-status">
            <div class="status-icon"></div>
            <span>Online</span>
        </div>
        <div class="minimize-button">&#x2212;</div>  <!-- Unicode for minus sign (−) -->

    </div> 






    <div class="loom-chatbot-body">
        <div class="loom-chatbot-messages"></div>
        <div class="loom-chatbot-loading">
            <img src="<?php echo plugin_dir_url(__FILE__); ?>images/loading.gif" alt="Loading...">
        </div>
    </div>
    
    <div class="loom-chatbot-footer">
        <input id="loom-chatbot-input" type="text" placeholder="Type a message...">
        <button id="start-voice">🎤 Speak</button>
        <button id="loom-chatbot-send">
            <img src="<?php echo plugin_dir_url(__FILE__); ?>images/send.png" alt="Send">
        </button>
    </div>
</div>
    <?php
    return ob_get_clean();
}
add_shortcode('loom_chatbot', 'loom_chatbot_shortcode');

// AJAX handler function for chatbot interaction
function loom_chatbot_respond() {
    check_ajax_referer('loom_chatbot_nonce', 'security');

    $question = sanitize_text_field($_POST['question']);
    $user_id = get_current_user_id();

    // Retrieve and update the conversation history
    $conversation_history = get_user_meta($user_id, 'loom_chatbot_conversation', true);
    if (!$conversation_history) {
        $conversation_history = [];
    }

    // Add the new question to the conversation history
    $conversation_history[] = ['role' => 'user', 'content' => $question];

    // Check FAQ database first
    global $faq_database;
    foreach ($faq_database as $faq_question => $faq_answer) {
        if (stripos($question, $faq_question) !== false) {
            // Add the bot's response to the conversation history
            $conversation_history[] = ['role' => 'bot', 'content' => $faq_answer];
            update_user_meta($user_id, 'loom_chatbot_conversation', $conversation_history);
            wp_send_json_success(['answer' => $faq_answer]);
            wp_die();
        }
    }

    // Define allowed topics for Gemini API
    $allowed_topics = [
        'web development',
        'wordpress',
        'AI tools',
        'software development'
    ];

    // Check if the question is related to the allowed topics
    $is_relevant_topic = false;
    foreach ($allowed_topics as $topic) {
        if (stripos($question, $topic) !== false) {
            $is_relevant_topic = true;
            break;
        }
    }

    if ($is_relevant_topic) {
        // Query the Gemini API
        $api_key = 'AIzaSyB4faUi9I-JDsC_1mNSBgnax8oqdbCtaS0'; // Replace with your actual Gemini API key
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $api_key;

        $headers = [
            'Content-Type' => 'application/json'
        ];

        // Prepare the conversation history for the API request
        $api_content = [];
        foreach ($conversation_history as $entry) {
            $api_content[] = ['text' => $entry['content']];
        }
        $api_content[] = ['text' => $question];  // Add the current question as the last part

        $body = [
            'contents' => [
                [
                    'parts' => $api_content
                ]
            ]
        ];

        $args = [
            'method'    => 'POST',
            'headers'   => $headers,
            'body'      => json_encode($body),
            'timeout'   => 120
        ];

        $response = wp_remote_request($api_url, $args);
        $responseBody = wp_remote_retrieve_body($response);
        $decoded = json_decode($responseBody, true);

        if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            $answer = $decoded['candidates'][0]['content']['parts'][0]['text'];

            // Add the bot's response to the conversation history
            $conversation_history[] = ['role' => 'bot', 'content' => $answer];
            update_user_meta($user_id, 'loom_chatbot_conversation', $conversation_history);

            wp_send_json_success(['answer' => $answer]);
        } else {
            wp_send_json_error('No response generated');
        }
    } else 
    {
        // Provide a professional response if the topic is not covered
        $professional_response = "I’m sorry, but I’m not able to provide an answer to that question. If you have questions about web development, WordPress, AI tools, or software development, feel free to ask!";
        // Add the bot's response to the conversation history
        $conversation_history[] = ['role' => 'bot', 'content' => $professional_response];
        update_user_meta($user_id, 'loom_chatbot_conversation', $conversation_history);

        wp_send_json_success(['answer' => $professional_response]);
    }

    wp_die();
}
add_action('wp_ajax_loom_chatbot_respond', 'loom_chatbot_respond');
add_action('wp_ajax_nopriv_loom_chatbot_respond', 'loom_chatbot_respond');

   
   // FAQ Database
$faq_database = [
    // General Information
    "What is your Name?" => "My name is Loom.",
    "What is LoomVision?" => "LoomVision is an AI-powered platform providing a suite of innovative tools designed to help you create professional, engaging, and optimized content with ease.",
    "Where are you located?" => "LoomVision is based in Northampton.",
    "How can I contact support?" => "You can contact our support team by emailing urooj_shafait292@hotmail.com.",
    "What is your email address?" => "You can reach us at support@loomvision.com.",
    "How can I request a new feature?" => "To request a new feature, please contact our support team at support@loomvision.org and provide details about your request.",
    "Who can use LoomVision?" => "LoomVision is ideal for creators, marketers, and businesses looking to simplify and enhance their content creation and optimization process, regardless of their technical skills or resources.",
    "What are your business hours?" => "Our support team is available Monday to Friday, 9 AM to 5 PM GMT.",
    "How can I reset my password?" => "You can reset your password by going to the 'Forgot Password' section on the login page and following the instructions sent to your email.",

    // Greetings
    "Hi" => "Hello! How can I assist you today?",
    "Hello" => "Hi there! How can I help you?",
    "Good morning" => "Good morning! How can I assist you today?",
    "Good afternoon" => "Good afternoon! How can I help you?",
    "Good evening" => "Good evening! How can I assist you today?",
    "Hey" => "Hey there! How can I help you?",
    "How are you?" => "I'm doing well, thank you! How can I assist you today?",
    "What’s up?" => "Not much, just here to help you! What can I do for you?"
];


