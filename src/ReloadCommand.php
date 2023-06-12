<?php

declare(strict_types=1);

namespace shreyop200\AuthUI\Auth;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;

class ReloadCommand extends Command implements CommandExecutor {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("loginreload", "Reload player data", "/loginreload");
        $this->setPermission("login.reload");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender->hasPermission($this->getPermission())) {
            $this->plugin->reloadPlayerData();
            $sender->sendMessage("Player data reloaded.");
        } else {
            $sender->sendMessage("You don't have permission to use this command.");
        }
        return true;
    }
}
