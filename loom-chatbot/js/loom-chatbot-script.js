var recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
recognition.lang = 'en-US'; // Set the language
recognition.interimResults = false; // Only final results

jQuery(document).ready(function($) {
    var conversationHistory = [];  // Initialize an array to store the conversation history

    // Initialize the chatbot as minimized
    $('.loom-chatbot-container').addClass('minimized');

    // Handle click on minimized chatbot to maximize
    $('.loom-chatbot-container').on('click', function() {
        if ($(this).hasClass('minimized')) {
            $(this).removeClass('minimized').addClass('maximized');
            $(this).find('.loom-chatbot-body').show();
            $(this).find('.loom-chatbot-footer').show();
            loadConversationHistory();  // Load conversation history when chatbot is maximized
        }
    });

    // Toggle functionality for minimizing the chatbot
    $('.minimize-button').on('click', function(e) {
        e.stopPropagation(); // Prevent the click event from bubbling up
        var $container = $(this).closest('.loom-chatbot-container');
        if (!$container.hasClass('minimized')) {
            $container.removeClass('maximized').addClass('minimized');
            $container.find('.loom-chatbot-body').hide();
            $container.find('.loom-chatbot-footer').hide();
        }
    });





    $('#start-voice').on('click', function() {
        recognition.start();
    });
    
    recognition.addEventListener('result', function(event) {
        var voiceInput = event.results[0][0].transcript;
        $('#loom-chatbot-input').val(voiceInput);
        $('#loom-chatbot-send').click();
    });
    
    recognition.addEventListener('end', function() {
        recognition.stop();
    });






    // function speak(text) {
    //     var utterance = new SpeechSynthesisUtterance(text);
    //     utterance.lang = 'en-US';
    //     window.speechSynthesis.speak(utterance);
    // }
    function speak(message) {
        var utterance = new SpeechSynthesisUtterance(message);
        utterance.onstart = function() {
            $('.loom-chatbot-messages').append('<div class="bot-message">🔊 Speaking...</div>');
        };
        utterance.onend = function() {
            $('.bot-message:contains("🔊 Speaking...")').remove();
        };
        window.speechSynthesis.speak(utterance);
    }
    
    // Add voice input functionality
    function startVoiceRecognition() {
        if ('webkitSpeechRecognition' in window) {
            var recognition = new webkitSpeechRecognition();
            recognition.lang = 'en-US';
            recognition.continuous = false;
            recognition.interimResults = false;
    
            recognition.onstart = function() {
                $('.loom-chatbot-messages').append('<div class="bot-message">🎤 Listening...</div>');
            };
    
            recognition.onresult = function(event) {
                var transcript = event.results[0][0].transcript;
                $('#loom-chatbot-input').val(transcript);
                $('.bot-message:contains("🎤 Listening...")').remove();
                $('#loom-chatbot-send').click(); // Automatically send the recognized voice message
            };
    
            recognition.onerror = function() {
                $('.bot-message:contains("🎤 Listening...")').remove();
                $('.loom-chatbot-messages').append('<div class="bot-message">❌ Voice recognition failed.</div>');
            };
    
            recognition.start();
        } else {
            alert("Voice recognition not supported in this browser.");
        }
    }
    
    // Add a voice input button next to the send button
    $('.loom-chatbot-footer').prepend('<button id="loom-chatbot-voice"><img src="voice-icon.png" alt="Voice"></button>');
    
    $('#loom-chatbot-voice').on('click', function() {
        startVoiceRecognition();
    });
    
    // User Guide for Voice Input
    $('.loom-chatbot-messages').append('<div class="bot-message">💬 Tip: You can use voice input by clicking the microphone button next to the send button.</div>');
    
    
    


   

    // Send message to the chatbot
    $('#loom-chatbot-send').on('click', function() {
        var question = $('#loom-chatbot-input').val();
        if (question === '') {
            return;
        }

        // Add user's question to the conversation history
        conversationHistory.push({role: 'user', content: question});
        displayMessage('user', question);

        $('#loom-chatbot-input').val('');
        $('.loom-chatbot-loading').show();

        $.ajax({
            type: 'POST',
            url: loom_chatbot_obj.ajax_url,
            data: {
                action: 'loom_chatbot_respond',
                question: question,
                security: loom_chatbot_obj.nonce
            },
            success: function(response) {
                $('.loom-chatbot-loading').hide();
                if (response.success) {
                    var answer = response.data.answer;
                    // Add bot's response to the conversation history
                    conversationHistory.push({role: 'bot', content: answer});
                    displayMessage('bot', answer);
                } else {
                    displayMessage('bot', 'I\'m sorry, I couldn\'t find an answer to your question.');
                }
            },
            error: function() {
                $('.loom-chatbot-loading').hide();
                displayMessage('bot', 'An error occurred. Please try again later.');
            }
        });
    });
     
    
    // Handle "Enter" key press to send message
    $('#loom-chatbot-input').on('keypress', function(e) {
        if (e.which == 13) {
            $('#loom-chatbot-send').click();
        }
    });

    // function displayMessage(role, message) {
    //     var messageClass = (role === 'user') ? 'user-message' : 'bot-message';
    //     var messageHtml = '<div class="' + messageClass + '">' + message + '</div>';
    //     $('.loom-chatbot-messages').append(messageHtml);
    
    //     var messagesContainer = $('.loom-chatbot-messages');
    //     // Scroll to the bottom
    //     messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    // }
    function displayMessage(role, message) {
        var messageClass = (role === 'user') ? 'user-message' : 'bot-message';
        var messageHtml = '<div class="' + messageClass + '">' + message + '</div>';
        $('.loom-chatbot-messages').append(messageHtml);
    
        var messagesContainer = $('.loom-chatbot-messages');
        // Scroll to the bottom
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    
        if (role === 'bot') {
            speak(message); // This will trigger the voice output for the bot's message
        }
    }
    
      
    // Function to load conversation history
    function loadConversationHistory() {
        // Clear current messages
        $('.loom-chatbot-messages').empty();
        // Display each message from the conversation history
        conversationHistory.forEach(function(entry) {
            displayMessage(entry.role, entry.content);
        });
    }
});
