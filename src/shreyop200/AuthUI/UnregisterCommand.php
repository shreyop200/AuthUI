<?php

declare(strict_types=1);

namespace shreyop200\AuthUI;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class UnregisterCommand extends Command {

    private $plugin;

    public function __construct(Main $plugin, string $name) {
        parent::__construct($name, "Unregister a Logged-in Player", "/$name", ["unlink"]);
        $this->plugin = $plugin;
        $this->setPermission("authui.command.unregister");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        
        /*$playerName = $args[0];
        $player = $this->plugin->getServer()->getPlayer($playerName);
        if ($player === null) {
            $sender->sendMessage(TextFormat::RED . "Player not found.");
            return true;
        }

        if (!$this->plugin->hasRegistered($player)) {
            $sender->sendMessage(TextFormat::RED . "The player is not registered.");
            return true;
        }

        $this->plugin->unregisterPlayer($player);
        $sender->sendMessage(TextFormat::GREEN . "The player has been unregistered successfully.");

        return true;*/
    }
}


    
       
