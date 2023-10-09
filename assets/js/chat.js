function cropText() {
    $('.last-message').each(function() {
        const $this = $(this);
        const text = $this.text().trim();

        if (text.length > 50) {
            const croppedText = text.substring(0, 50) + '...';
            $this.text(croppedText);
        }
    });
}

function updateTimestamps() {
    $('.last-message-time').each(function() {
        const $this = $(this);
        const timestampText = $this.text().trim();

        if (timestampText === 'just now') {
            let elapsedMinutes = 1;

            const updateInterval = setInterval(function() {
                elapsedMinutes++;
                if (elapsedMinutes === 1) {
                    $this.text('1 minute ago');
                } else {
                    $this.text(elapsedMinutes + ' minutes ago');
                }
            }, 60000);

            $this.data('update-interval', updateInterval);
        } else if (timestampText.includes('minute')) {
            const minutesAgo = parseInt(timestampText);
            const newTimestamp = (minutesAgo + 1) + ' minutes ago';
            $this.text(newTimestamp);
        } else if (timestampText.includes('hour')) {
            const hoursAgo = parseInt(timestampText);
            const newTimestamp = (hoursAgo + 1) + ' hours ago';
            $this.text(newTimestamp);
        }
    });
}

// Call the updateTimestamps function initially
updateTimestamps();

// Call the updateTimestamps function periodically (e.g., every minute)
setInterval(updateTimestamps, 60000); // 60000 milliseconds = 1 minute

function createMessageTemplate(message, isSender) {
    let messageType = isSender ? 'sender' : 'receiver';
    return `
        <li class="d-flex justify-content-between mb-4 ${messageType}-message">
            <img src="${message.profilePicture}" alt="avatar" class="rounded-circle d-flex align-self-start me-3 shadow-1-strong" width="60" height="60">
            <div class="card ${isSender ? 'w-100' : ''}">
                <div class="card-header d-flex justify-content-between">
                    <p class="fw-bold mb-0">${message.senderName}</p>
                    <p class="text-muted small mb-0"><i class="far fa-clock"></i> ${message.date}</p>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        ${message.message}
                    </p>
                </div>
            </div>
        </li>
    `;
}

function fetchMessages(receiverId) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            type: "GET",
            url: "/panel/chat/get-conversation/" + receiverId,
            contentType: "application/json",
            success: resolve,
            error: reject
        });
    });
}

function fetchLastMessages(receiverId) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            type: "GET",
            url: `/panel/chat/get-last-message/${receiverId}`,
            contentType: "application/json",
            success: resolve,
            error: reject
        });
    });
}

let newMessagesCounter = {};

function updateBadge(chatId) {
    const badge =$(`.contact-list a#${chatId} .new-messages-counter`);
    const count = newMessagesCounter[chatId] || 0;

    if (count > 0) {
        badge.text(count).show();
    } else {
        badge.text(count).hide();
    }
}

function handle_chat() {
    if (window.location.pathname === '/panel/chat') {
        const chat = $('.chat-window');
        let receiverId = chat.data('receiver-id');
        const senderId = chat.data('sender-id');
        const senderName = chat.data('sender-name');
        let receiverName = chat.data('receiver-name');
        let messageContainer = $('.message-container');
        const firstContact = $('.contact-list li:first');
        $('#chat-input-box').focus();
        $('.contact-list li.p-2').each(function() {
            let receiverId = $(this).find('a').attr('id');
            let lastMessageElement = $(this).find('.last-message');
            let lastMessageTimeElement = $(this).find('.last-message-time');

            fetchLastMessages(receiverId)
            .then(function(response){
                lastMessageElement.text(response.sender + ": " + response.lastMessage);
                lastMessageTimeElement.text(response.time);
            })
            .catch(function(error) {
                console.error("Error while getting messages:", error);
            });
        });


        const senderProfilePicture = $('.profile-picture-nav').attr('src');
        let receiverProfilePicture = firstContact.find('img').attr('src');
        firstContact.addClass('active');

        fetchMessages(receiverId)
            .then(function(response) {
                response.forEach(function(message) {
                    let isSender = message.senderId === senderId;
                    message.profilePicture = '/uploads/profile_pictures/' + message.profilePicture;
                    let messageTemplate = createMessageTemplate(message, isSender);
                    messageContainer.prepend(messageTemplate);
                });
                messageContainer.scrollTop(messageContainer[0].scrollHeight);
                cropText();
            })
            .catch(function(error) {
                console.error("Error while getting messages:", error);
            });

        $('.contact-list a').on('click', function(event) {
            event.preventDefault();
            receiverId = $(this).attr('id');
            receiverName = $(this).data('receiver-name');
            receiverProfilePicture = $(this).find('img').attr('src');
            $('.active').removeClass('active');
            $(this).parent().addClass('active');
            chat.attr('data-receiver-id', receiverId);
            newMessagesCounter[receiverId] = 0;
            updateBadge(receiverId);
            fetchMessages(receiverId)
                .then(function(response) {
                    let messages = response;
                    let messageContainer = $('.message-container');

                    messageContainer.empty();
                    messages.forEach(function(message) {
                        message.profilePicture = '/uploads/profile_pictures/' + message.profilePicture;
                        let isSender = message.senderId === senderId;
                        let messageTemplate = createMessageTemplate(message, isSender);
                        messageContainer.prepend(messageTemplate);
                    });
                    messageContainer.scrollTop(messageContainer[0].scrollHeight);
                    $('#chat-input-box').focus();
                })
                .catch(function(error) {
                    console.error("Error while getting messages:", error);
                });

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
                    senderName: senderName,
                    receiverName: receiverName,
                    profilePicture: senderProfilePicture,
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
                            messageData.date = response.date;
                            let messageTemplate = createMessageTemplate(messageData, true);
                            messageContainer.append(messageTemplate);
                            messageContainer.scrollTop(messageContainer[0].scrollHeight);
                            conn.send(JSON.stringify(messageData));
                            let lastMessageElement = $(`.contact-list a#${messageData.receiver}`).find('.last-message');
                            let lastMessageTimeElement = $(`.contact-list a#${messageData.receiver}`).find('.last-message-time');
                            lastMessageElement.text(messageData.senderName + ": " + messageData.message);
                            lastMessageTimeElement.text('just now');
                            cropText();
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
                let messageTemplate = createMessageTemplate(receivedData, false);
                console.log("Not yet!", receivedData.sender, receiverId);
                if (receivedData.sender == receiverId) {
                    console.log("MESSAGE!", receivedData.sender, receiverId);
                    messageContainer.append(messageTemplate);
                    messageContainer.scrollTop(messageContainer[0].scrollHeight);
                }
                let lastMessageElement = $(`.contact-list a#${receivedData.sender}`).find('.last-message');
                lastMessageElement.text(receivedData.senderName + ": " + receivedData.message);
                let lastMessageTimeElement = $(`.contact-list a#${receivedData.sender}`).find('.last-message-time');
                lastMessageTimeElement.text('just now');
                cropText();
                if (!newMessagesCounter[receivedData.sender]) {
                    newMessagesCounter[receivedData.sender] = 1;
                } else {
                    newMessagesCounter[receivedData.sender]++;
                }

                updateBadge(receivedData.sender);
            }
        };
    }
}

export {handle_chat}