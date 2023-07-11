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
        
        # TODO: Complete it
    }
}


    
       
