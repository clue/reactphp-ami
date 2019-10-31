<?php

namespace Clue\React\Ami\Protocol;

/**
 * The `Collection` value object represents an incoming response received from the AMI
 * for certain actions that return a list of entries.
 * It shares all properties of the [`Response`](#response) parent class.
 *
 * You can access the `Collection` like a normal `Response` in order to access
 * the leading `Response` for this collection or you can use the below methods
 * to access the list entries and completion event.
 *
 * ```
 * Action: CoreShowChannels
 *
 * Response: Success
 * EventList: start
 * Message: Channels will follow
 *
 * Event: CoreShowChannel
 * Channel: SIP / 123
 * ChannelState: 6
 * ChannelStateDesc: Up
 * …
 *
 * Event: CoreShowChannel
 * Channel: SIP / 456
 * ChannelState: 6
 * ChannelStateDesc: Up
 * …
 *
 * Event: CoreShowChannel
 * Channel: SIP / 789
 * ChannelState: 6
 * ChannelStateDesc: Up
 * …
 *
 * Event: CoreShowChannelsComplete
 * EventList: Complete
 * ListItems: 3
 * ```
 */
class Collection extends Response
{
    private $entryEvents;
    private $completeEvent;

    public function __construct(Response $response, array $entryEvents, Event $completeEvent)
    {
        $this->fields = $response->getFields();
        $this->entryEvents = $entryEvents;
        $this->completeEvent = $completeEvent;
    }

    /**
     * Get the list of all intermediary `Event` objects where each entry represents a single entry in the collection.
     *
     * ```php
     * foreach ($collection->getEntryEvents() as $entry) {
     *     assert($entry instanceof Clue\React\Ami\Protocol\Event);
     *     echo $entry->getFieldValue('Channel') . PHP_EOL;
     * }
     * ```
     *
     * @return Event[]
     */
    public function getEntryEvents()
    {
        return $this->entryEvents;
    }

    /**
     * Get the trailing `Event` that completes this collection.
     *
     * ```php
     * echo $collection->getCompleteEvent()->getFieldValue('ListItems') . PHP_EOL;
     * ```
     *
     * @return Event
     */
    public function getCompleteEvent()
    {
        return $this->completeEvent;
    }
}
