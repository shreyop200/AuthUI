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
use pocketmine\AsyncTask;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskScheduler;
use SQLite3;
use SQLite3Result;

class Main extends PluginBase implements Listener {

    private $players;
    private $config;
    private $database;

    public function onEnable(): void {
        $this->players = [];
        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->config = $this->getConfig();

        $this->createDataFolder();

        $this->database = new SQLite3($this->getDataFolder() . "player_data.db");
        $this->initializeDatabase();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register($this->getName(), new ReloadCommand($this, $this->getName()));
        $this->getServer()->getCommandMap()->register($this->getName(), new UnregisterCommand($this, $this->getName()));

        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function (/*int $currentTick*/): void {
            $this->loadPlayerData();
        }), 1);
    }

    private function createDataFolder(): void {
        $dataFolderPath = $this->getDataFolder();
        if (!is_dir($dataFolderPath)) {
            mkdir($dataFolderPath);
        }
    }

    private function initializeDatabase(): void {
        $createTableQuery = "CREATE TABLE IF NOT EXISTS players (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            playerName TEXT NOT NULL,
            password TEXT NOT NULL,
            loginStreak INTEGER DEFAULT 0
        )";

        $this->database->exec($createTableQuery);
    }

    private function loadPlayerData(): void {
        $result = $this->database->query("SELECT * FROM players");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $playerName = $row['playerName'];
            $this->players[$playerName] = $row;
        }
        $result->finalize();
    }

    private function hasRegistered(Player $player): bool {
        $playerName = $player->getName();
        return isset($this->players[$playerName]);
    }

    private function getPlayerData(Player $player): ?array {
        $playerName = $player->getName();
        return $this->players[$playerName] ?? null;
    }

    private function savePlayerData(Player $player, array $data): void {
        $playerName = $player->getName();
        $this->players[$playerName] = $data;

        $password = $this->database->escapeString($data['password']);
        $loginStreak = $data['loginStreak'];

        $existingData = $this->getPlayerData($player);
        if ($existingData === null) {
            $query = "INSERT INTO players (playerName, password, loginStreak) VALUES ('$playerName', '$password', $loginStreak)";
        } else {
            $query = "UPDATE players SET password = '$password', loginStreak = $loginStreak WHERE playerName = '$playerName'";
        }

        $this->database->exec($query);
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        if (!$this->hasRegistered($player)) {
            $this->showRegisterUI($player);
        } else {
            $this->showLoginUI($player);
        }
    }

    private function showRegisterUI(Player $player): void {
        $form = new CustomForm(function(Player $player, ?array $data): void {
            if ($data === null) {
                $player->kick(TextFormat::RED . "Registration cancelled", false);
                return;
            }
            $password = $data[0];
            $confirmPassword = $data[1];

            if ($password === $confirmPassword) {
                $playerData = [
                    'password' => $password,
                    'loginStreak' => 0
                ];
                $this->savePlayerData($player, $playerData);
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
        $playerData = $this->getPlayerData($player);
        $loginStreak = $playerData['loginStreak'] ?? 0;
        $loginTarget = $this->config->get("login_target", 5);
        $remainingLogins = $loginTarget - ($loginStreak % $loginTarget);

        $form = new CustomForm(function(Player $player, ?array $data) use ($remainingLogins): void {
            if ($data === null) {
                $player->kick(TextFormat::RED . "Login cancelled", false);
                return;
            }
            $password = $data[0];

            $playerData = $this->getPlayerData($player);
            $savedPassword = $playerData['password'];

            if ($password === $savedPassword) {
                $player->sendMessage($this->config->get("login_success_message"));

                $loginStreak = $playerData['loginStreak'] + 1;
                $playerData['loginStreak'] = $loginStreak;
                $this->savePlayerData($player, $playerData);

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

    public function unregisterPlayer(Player $player): void {
        $playerName = $player->getName();
        $this->database->exec("DELETE FROM players WHERE playerName = '$playerName'");
        unset($this->players[$playerName]);
        $player->sendMessage($this->config->get("unregister_success_message"));
    }

    public function reloadPlayerData(): void {
        $this->players = [];
        $this->loadPlayerData();
        $this->getLogger()->info("Player data reloaded.");
    }
}

        
    
            
       

        
           
