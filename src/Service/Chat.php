<?php

namespace App\Service;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    private array $userIdToResourceId = [];

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        // get user ID from client-side sent as parameter
        $query = $conn->httpRequest->getUri()->getQuery();
        parse_str($query, $queryParams);
        $senderId = $queryParams['senderId'];
        $this->userIdToResourceId[$senderId] = ['resourceId' => $conn->resourceId];

        echo "New connection! ({$senderId} connected)\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $message = json_decode($msg, true);

        if (isset($this->userIdToResourceId[$message['receiver']]))
        {
            $receiver = $this->userIdToResourceId[$message['receiver']]['resourceId'];
            foreach ($this->clients as $client) {
                if ($client->resourceId === $receiver && $from->resourceId !== $receiver) {
                    echo $msg . "\n";
                    $client->send($msg);
                }
            }
        }
        else {
            echo "Receiver is not connected! Saving into DB...\n";
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        foreach ($this->userIdToResourceId as $key => $user)
        {
            if (isset($user["resourceId"]) && $user["resourceId"] === $conn->resourceId) {
                unset($this->userIdToResourceId[$key]);
            }
        }

        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}