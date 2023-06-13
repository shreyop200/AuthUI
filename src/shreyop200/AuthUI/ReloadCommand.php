<?php

declare(strict_types=1);

namespace shreyop200\AuthUI;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;

class ReloadCommand extends Command {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("loginreload", "Reload player data", "/loginreload", ["lr"]);
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if ($sender->hasPermission("authui.command.reload")) {
            $this->plugin->reloadPlayerData();
            $sender->sendMessage("Player data reloaded.");
        } else {
            $sender->sendMessage("You don't have permission to use this command.");
        }
        return true;
    }
}
