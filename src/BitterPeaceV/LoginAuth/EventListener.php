<?php

namespace BitterPeaceV\LoginAuth;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class EventListener implements Listener
{
    private $plugin;

    public function __construct(LoginAuth $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onPlayerLogin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $player->setImmobile();

        if (empty(Database::getUserData($name))) {
            $player->sendMessage($this->plugin->getMessage("request.registration"));
            $this->plugin->register[] = $name;
        } else {
            $player->sendMessage($this->plugin->getMessage("request.password"));
            $this->plugin->login[] = $name;
        }
    }
}
