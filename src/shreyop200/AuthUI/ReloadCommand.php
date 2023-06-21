<?php

declare(strict_types=1);

namespace shreyop200\AuthUI;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;

class ReloadCommand extends Command implements PluginOwned {

    private $plugin;

    public function __construct(Main $plugin, string $name) {
        parent::__construct($name, "Reload player data", "/$name", ["lr"]);
        $this->plugin = $plugin;
        
        $this->setPermission("authui.command.reload");
        
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
       
            $this->plugin->reloadPlayerData();
            $sender->sendMessage("Player data reloaded.");
            return true;
    }

    public function getOwningPlugin(): Main {
        return $this->plugin;
    }
}
