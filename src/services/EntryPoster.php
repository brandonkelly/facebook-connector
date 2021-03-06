<?php
/**
 * FacebookConnector plugin for Craft CMS 3.x
 *
 * Connect a Website to an Facbook Page
 *
 * @link      https://www.itscoding.ch
 * @copyright Copyright (c) 2017 Simon Müller itsCoding
 */

namespace itscoding\facebookconnector\services;

use itscoding\facebookconnector\FacebookConnector;
use itscoding\facebookconnector\records\PostMemorize;
use craft\base\Component;
use craft\elements\Entry;
use itscoding\facebookconnector\services\post\PostCreator;
use itscoding\facebookconnector\services\post\AbstractPostHandler;
use itscoding\facebookconnector\services\post\PostUpdater;

/**
 * EntryPoster Service
 *
 * @author    Simon Müller itsCoding
 * @package   FacebookConnector
 * @since     0.1.0
 */
class EntryPoster extends Component
{


    /**
     * @param Entry $entry
     * @return array
     */
    private function getPostData(Entry $entry)
    {
        try {
            $config = include \Craft::$app->vendorPath . '/../' . 'fieldconfig.php';
        } catch (\Exception $e) {
            $config = function () {
                return [];
            };
        }
        $default = [
            'post_on_facebook' => $entry->post_on_facebook ?? true,
            'link' => $entry->getUrl(),
            'entry_id' => $entry->id,
            'message' => $entry->subTitle ?? '',
            'picture' => '',
            'caption' => '',
            'description' => ''
        ];
        return array_merge($default, $config($entry));
    }

    /**
     * called from the event-listener
     * FacebookConnector::$plugin->entryPoster->post()
     * @param Entry $entryType
     */
    public function post(Entry $entry)
    {
        $token = FacebookConnector::$plugin->tokenLoader->loadValidToken();
        if (!$token) {
            return $this->handleInvalidToken($token);
        }
        $postData = $this->getPostData($entry);
        if ($postData['post_on_facebook']) {
            $postHandler = $this->loadPostHandler($entry->getId());
            return $postHandler->post($postData, $token, $entry->getId());
        }
        return true;
    }


    private function loadPostHandler(string $entryId): AbstractPostHandler
    {
        if ((bool)PostMemorize::findOne(['entryId' => $entryId])) {
            return new PostUpdater();
        }
        return new PostCreator();
    }

    /**
     * @param $token
     * @return bool
     */
    private function handleInvalidToken($token)
    {
        //Todo do not post and send a message to the user what he clould to to fix this
        //maybe set a flash message
        var_dump($token, 'no token found');
        return false;
    }
}
