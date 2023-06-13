<?php

namespace shreyop200\AuthUI;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use jojoe77777\FormAPI;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\console\ConsoleCommandSender;

class Main extends PluginBase implements Listener {

    private $players;
    private $config;

    public function onEnable(): void {
        $this->players = [];
        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->config = $this->getConfig();

        $this->createDataFolder();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register("loginreload", new ReloadCommand($this));
    }

    private function createDataFolder(): void {
        $dataFolderPath = $this->getDataFolder() . 'data';
        if (!is_dir($dataFolderPath)) {
            mkdir($dataFolderPath);
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
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
        $playerFile = new Config($this->getDataFolder() . "data/" . $playerName . ".yml", Config::YAML);
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
        $playerName = $player->getName();
        $playerFile = $this->players[$playerName];

        $form = new CustomForm(function(Player $player, ?array $data): void {
            if ($data === null) {
                $player->kick(TextFormat::RED . "Registration cancelled", false);
                return;
            }
            $password = $data[0];
            $confirmPassword = $data[1];

            $playerName = $player->getName();
            $playerFile = $this->players[$playerName];

            if ($password === $confirmPassword) {
                $playerFile->set("password", $password);
                $playerFile->save();
                $player->sendMessage($this->config->get("register_success_message"));
            } else {
                $player->kick($this->config->get("password_mismatch_kick_message"), false);
            }
        });

        $form->setTitle($this->config->get("register_title"));
        $form->addInput("Enter a password", "Example: pw123");
        $form->addInput("Confirm password", "Example: pw123");

        $player->sendForm($form);
    }

    private function showLoginUI(Player $player): void {
        $playerName = $player->getName();
        $playerFile = $this->players[$playerName];

        $loginStreak = $playerFile->get("login_streak", 0);
        $loginTarget = $this->config->get("login_target", 5);
        $remainingLogins = $loginTarget - ($loginStreak % $loginTarget);

        $form = new CustomForm(function(Player $player, ?array $data) use ($remainingLogins): void {
            if ($data === null) {
                $player->kick(TextFormat::RED . "Login cancelled", false);
                return;
            }
            $password = $data[0];

            $playerName = $player->getName();
            $playerFile = $this->players[$playerName];
            $savedPassword = $playerFile->get("password");

            if ($password === $savedPassword) {
                $player->sendMessage($this->config->get("login_success_message"));

                $loginStreak = $playerFile->get("login_streak", 0) + 1;
                $playerFile->set("login_streak", $loginStreak);
                $playerFile->save();

                $this->checkLoginRewards($player, $loginStreak);

                $loginTarget = $this->config->get("login_target", 5);
                $remainingLogins = $loginTarget - ($loginStreak % $loginTarget);
                $player->sendMessage("You have logged in $loginStreak times. Log in $remainingLogins more times to receive a reward.");
            } else {
                $player->kick($this->config->get("invalid_password_kick_message"), false);
            }
        });

        $form->setTitle($this->config->get("login_title"));
        $form->addInput("Enter your password", "Example: pw123");

        $player->sendForm($form);
    }

    private function checkLoginRewards(Player $player, int $loginStreak): void {
        $loginRewardsEnabled = $this->config->get("login-rewards", false);
        if (!$loginRewardsEnabled) {
            return;
        }

        $rewardsConfig = $this->config->get("login-reward", []);

        foreach ($rewardsConfig as $milestone => $command) {
            if ($loginStreak >= $milestone) {
                $command = str_replace("{player}", $player->getName(), $command);
                $this->getServer()->dispatchCommand(new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()), $command);

                $rewardMessage = str_replace("{milestone}", (string)$milestone, $this->config->get("reward_message"));
                $player->sendMessage($rewardMessage);

            }
        }
    }



    public function reloadPlayerData(): void {
        foreach ($this->players as $playerName => $playerFile) {
            $player = $this->getServer()->getPlayerExact($playerName);
            if ($player !== null) {
                $player->sendMessage($this->config->get("reload_message"));
                $this->unloadPlayerData($player);
                $this->loadPlayerData($player);
            }
        }
        $this->getLogger()->info("Player data reloaded.");
    }

    public function forgotPassword(Player $player): void {
        $player->sendMessage($this->config->get("forgot_password_message"));
    }
}
