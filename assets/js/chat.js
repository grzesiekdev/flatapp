function createMessageTemplate(message, isSender) {
    let messageType = isSender ? 'sender' : 'receiver';

    return `
                        <li class="d-flex justify-content-between mb-4 ${messageType}-message">
                            ${isSender ? '' : `<img src="/uploads/profile_pictures/${message.profilePicture}" alt="avatar" class="rounded-circle d-flex align-self-start me-3 shadow-1-strong" width="60" height="60">`}
                            <div class="card ${isSender ? 'w-100' : ''}">
                                <div class="card-header d-flex justify-content-between">
                                    <p class="fw-bold mb-0">${message.sender}</p>
                                    <p class="text-muted small mb-0"><i class="far fa-clock"></i> ${message.date}</p>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">
                                        ${message.message}
                                    </p>
                                </div>
                            </div>
                            ${isSender ? `<img src="/uploads/profile_pictures/${message.profilePicture}" alt="avatar" class="rounded-circle d-flex align-self-start ms-3 shadow-1-strong" width="60" height="60">` : ''}
                        </li>
                    `;
}

function handle_chat() {
    if (window.location.pathname === '/panel/chat') {
        fetch('/panel/chat/get-data')
            .then(response => response.json())
            .then(data => {
                const chat = $('.chat-window');
                let receiverId = chat.data('receiver-id');
                const senderId = chat.data('sender-id');

                    $.ajax({
                        type: "GET",
                        url: "/panel/chat/get-conversation/" + receiverId,
                        contentType: "application/json",
                        success: function(response) {
                            let messages = response;
                            let messageContainer = $('.message-container');

                            messages.forEach(function(message) {
                                let isSender = message.senderId === senderId;
                                let messageTemplate = createMessageTemplate(message, isSender);
                                messageContainer.prepend(messageTemplate);
                            });
                        },
                        error: function(error) {
                            console.error("Error while getting messages:", error);
                        }
                    });
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
                    if (conn.readyState === WebSocket.OPEN) {
                        const messageData = {
                            sender: senderId,
                            receiver: receiverId,
                            message: message,
                            date: data.date
                        };

                        $('#chat-input-box').val('');
                        $.ajax({
                            type: "POST",
                            url: "/panel/chat/save-into-db",
                            data: JSON.stringify(messageData),
                            contentType: "application/json",
                            success: function(response) {
                                console.log("Message saving status:", response);
                                if (response.status  === "success") {
                                    messageData.status = "success";
                                    conn.send(JSON.stringify(messageData));
                                }
                            },
                            error: function(error) {
                                console.error("Error saving message:", error);
                            }
                        });
                    }
                });

                conn.onmessage = function(e) {
                    const receivedData = JSON.parse(e.data);
                    if (receivedData.status === "success") {
                        console.log('Received message:', receivedData);
                    }
                };
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
}

export {handle_chat}