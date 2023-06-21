<?php

declare(strict_types=1);

namespace shreyop200\AuthUI;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;

class ReloadCommand extends Command implements PluginOwned {

    private $plugin;

    public function __construct(Main $plugin, string $name) {
        parent::__construct($name, "Reload player data", "/$name", [$name]);
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender->hasPermission("authui.command.reload")) {
            $this->plugin->reloadPlayerData();
            $sender->sendMessage("Player data reloaded.");
        } else {
            $sender->sendMessage("You don't have permission to use this command.");
        }
        return true;
    }

    public function getOwningPlugin(): Main {
        return $this->plugin;
    }
}
