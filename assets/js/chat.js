import Cookies from 'js-cookie';

function handle_chat() {
    if (window.location.pathname === '/panel/chat') {
        fetch('/panel/chat/get-data')
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    const chat = $('.chat-window');
                    let receiverId = chat.data('receiver-id');
                    const senderId = chat.data('sender-id');
                    $('.contact-list a').on('click', function(event) {
                        event.preventDefault(); // Prevent the default link behavior
                        receiverId = $(this).attr('id');
                        chat.attr('data-receiver-id', receiverId);
                    });
                    const conn = new WebSocket(`ws://localhost:8080?receiverId=${receiverId}&senderId=${senderId}`);
                    conn.onopen = function(e) {
                        console.log("Connection established!");
                    };

                    $('#chat-input-box').on('keydown', function(event) {
                        if (event.keyCode === 13 && !event.shiftKey) {
                            event.preventDefault();

                            $('.send-message').click();
                        }
                    });

                    $('.send-message').on('click', function() {
                        const message = $('#chat-input-box').val();
                        const sender = data.userId;
                        if (conn.readyState === WebSocket.OPEN) {
                            const messageData = {
                                sender: sender,
                                receiver: receiverId,
                                message: message
                            };
                            conn.send(JSON.stringify(messageData));
                            $('#chat-input-box').val('');
                        }
                    });

                    conn.onmessage = function(e) {
                        const receivedData = JSON.parse(e.data);
                        console.log('Received message:', receivedData);
                    };
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
}

export {handle_chat}