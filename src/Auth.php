<?php

namespace shreyop200\AuthUI\Auth;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use jojoe77777\FormAPI;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase {

    private $players;

    public function onEnable(): void {
        $this->players = [];
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("loginreload", new ReloadCommand($this));
    }

    public function onPlayerJoin(Player $player): void {
        $this->loadPlayerData($player);
        if (!$this->hasRegistered($player)) {
            $this->showRegisterUI($player);
        } else {
            $this->showLoginUI($player);
        }
    }

    public function onPlayerQuit(Player $player): void {
        $this->unloadPlayerData($player);
    }

    private function loadPlayerData(Player $player): void {
        $playerName = $player->getName();
        $playerFile = new Config($this->getDataFolder() . $playerName . ".yml", Config::YAML);
        $this->players[$playerName] = $playerFile;
    }

    private function unloadPlayerData(Player $player): void {
        $playerName = $player->getName();
        if (isset($this->players[$playerName])) {
            unset($this->players[$playerName]);
        }
    }

    private function hasRegistered(Player $player): bool {
        $playerName = $player->getName();
        return $this->players[$playerName]->exists("password");
    }

    private function showRegisterUI(Player $player): void {
        $form = new CustomForm(function(Player $player, ?array $data): void {
            if ($data === null) {
                $player->kick(TextFormat::RED . "Registration cancelled", false);
                return;
            }
            $password = $data[1];
            $playerName = $player->getName();
            $playerFile = $this->players[$playerName];
            $playerFile->set("password", $password);
            $playerFile->save();
            $player->sendMessage(TextFormat::GREEN . "You have successfully registered!");
        });
        $form->setTitle("§l§6Register");
        $form->addInput("Enter a password", "Example: pw123");
        $form->addInput("Confirm password", "Example: pw123");
        $player->sendForm($form);
    }

    private function showLoginUI(Player $player): void {
        $form = new CustomForm(function(Player $player, ?array $data): void {
            if ($data === null) {
                $player->kick(TextFormat::RED . "Login cancelled", false);
                return;
            }
            $password = $data[0];
            $playerName = $player->getName();
            $playerFile = $this->players[$playerName];
            $savedPassword = $playerFile->get("password");
            if ($password === $savedPassword) {
                $player->sendMessage(TextFormat::GREEN . "You have successfully logged in!");
            } else {
                $player->kick(TextFormat::RED . "INVALID PASSWORD\n\n§eIf you forget your Password, please contact the Admin/Owner", false);
            }
        });
        $form->setTitle("§l§6Login");
        $form->addInput("Enter your password", "Example: pw123");
        $player->sendForm($form);
    }

    public function resetPlayerPassword(Player $player): void {
        $playerName = $player->getName();
        $playerFile = $this->players[$playerName];
        $playerFile->remove("password");
        $playerFile->save();
        $player->sendMessage(TextFormat::YELLOW . "Your password has been reset. Please register again.");
        $this->showRegisterUI($player);
    }

    public function reloadPlayerData(): void {
        foreach ($this->players as $playerName => $playerFile) {
            $player = $this->getServer()->getPlayerExact($playerName);
            if ($player !== null) {
                $player->sendMessage(TextFormat::YELLOW . "Reloading data...");
                $this->unloadPlayerData($player);
                $this->loadPlayerData($player);
            }
        }
        $this->getLogger()->info("Player data reloaded.");
    }

    public function forgotPassword(Player $player): void {
        $playerName = $player->getName();
        $playerFile = $this->players[$playerName];
        if ($playerFile->exists("password")) {
            $playerFile->remove("password");
            $playerFile->save();
            $player->sendMessage(TextFormat::YELLOW . "Your password has been reset. Please register again.");
            $this->showRegisterUI($player);
        } else {
            $player->sendMessage(TextFormat::RED . "You have not registered yet!");
        }
    }
}
