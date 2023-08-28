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
        $this->database = new SQLite3($this->getDataFolder() . "auth.db", SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $this->initializeDatabase();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register($this->getName(), new ReloadCommand($this, $this->getName()));

        $this->loadAllPlayerData();
        $this->getLogger()->info("AuthUI Plugin has been enabled.");
    }

    public function onDisable(): void {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $playerName = $player->getName();
            $playerData = $this->getPlayerData($player);
        
            if ($playerData !== null) {
                $this->savePlayerData($player, $playerData);
            }
        }
        $this->database->close();
        $this->getLogger()->info("AuthUI Plugin has been disabled.");
    }

    private function createDataFolder(): void {
        $dataFolderPath = $this->getDataFolder();
        if (!is_dir($dataFolderPath)) {
            mkdir($dataFolderPath);
        }
    }

    public function reloadPlayerData(): void {
        $this->players = [];
        $this->loadAllPlayerData();
        $this->getLogger()->info("Player data reloaded.");
    }

    private function initializeDatabase(): void {
        $createTableQuery = "CREATE TABLE IF NOT EXISTS players (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            playerName TEXT NOT NULL,
            password TEXT NOT NULL,
            loginStreak INTEGER DEFAULT 0,
            registered INTEGER DEFAULT 0
        )";

        $result = $this->database->exec($createTableQuery);

        if ($result !== false) {
            $this->getLogger()->info("Database table 'players' initialized.");
        } else {
            $this->getLogger()->error("Failed to initialize database table 'players'.");
        }
    }

    private function loadAllPlayerData(): void {
        $query = "SELECT * FROM players";
        $stmt = $this->database->prepare($query);

        if ($stmt === false) {
            $this->getLogger()->error("Failed to prepare database query: " . $this->database->lastErrorMsg());
            return;
        }

        $result = $stmt->execute();

        if ($result !== false) {
            $loadedData = 0;

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $playerName = $row['playerName'];
                $this->players[$playerName] = $row;
                $loadedData++;
            }

            $stmt->close();

            if ($loadedData > 0) {
                $this->getLogger()->info("Loaded data for $loadedData players.");
            } else {
                $this->getLogger()->warning("No player data found in the database.");
            }
        } else {
            $this->getLogger()->error("Failed to execute database query: " . $this->database->lastErrorMsg());
        }
    }


    private function loadPlayerData(Player $player): void {
        $playerName = $player->getName();
        $query = "SELECT * FROM players WHERE playerName = :name";
        $stmt = $this->database->prepare($query);
        $stmt->bindValue(":name", $playerName, SQLITE3_TEXT);
        
        $result = $stmt->execute();

        if ($result !== false) {
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if ($row !== false) {
                $this->showLoginUI($player);
            } else {
                $this->showRegisterUI($player);
            }
            $stmt->close();
        } else {
            $this->getLogger()->error("Failed to execute database query: " . $this->database->lastErrorMsg());
        }
    }



    /**
     * @param Player $player
     * @return bool|array Returns an array with player data if found, or false if not found.
      */
    private function getPlayerData(Player $player) {
        $playerName = $player->getName();
        $query = "SELECT * FROM players WHERE playerName = :name";
        $stmt = $this->database->prepare($query);
        $stmt->bindValue(":name", $playerName, SQLITE3_TEXT);
        
        $result = $stmt->execute();

        if ($result !== false) {
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $stmt->close();
            return $row;
        } else {
            $this->getLogger()->error("Failed to execute database query: " . $this->database->lastErrorMsg());
        }
        
        return null;
    }

    private function savePlayerData(Player $player, array $data): void {
        $playerName = $player->getName();
        $password = $data['password'];
        $loginStreak = $data['loginStreak'];
        $registered = isset($data['registered']) ? $data['registered'] : 0;

        $query = "INSERT OR REPLACE INTO players (playerName, password, loginStreak, registered) VALUES (:name, :password, :loginStreak, :registered)";
        $stmt = $this->database->prepare($query);
        $stmt->bindValue(":name", $playerName, SQLITE3_TEXT);
        $stmt->bindValue(":password", $password, SQLITE3_TEXT);
        $stmt->bindValue(":loginStreak", $loginStreak, SQLITE3_INTEGER);
        $stmt->bindValue(":registered", $registered, SQLITE3_INTEGER);

        $result = $stmt->execute();

        if ($result !== false) {
            $stmt->close();
        } else {
            $this->getLogger()->error("Failed to save data for player: $playerName");
        }
    }

    private function hasRegistered(Player $player): bool {
        $playerName = $player->getName();
        $query = "SELECT registered FROM players WHERE playerName = :name";
        $stmt = $this->database->prepare($query);
        $stmt->bindValue(":name", $playerName, SQLITE3_TEXT);

        $result = $stmt->execute();

        if ($result !== false) {
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $stmt->close();
            return ($row !== false) && ($row['registered'] === 1);
        } else {
            $this->getLogger()->error("Failed to execute database query: " . $this->database->lastErrorMsg());
        }

        return false;
    }

    private function setPlayerRegistered(Player $player, bool $registered): void {
        $playerName = $player->getName();
        $registeredValue = $registered ? 1 : 0;
        
        $query = "UPDATE players SET registered = :registered WHERE playerName = :name";
        $stmt = $this->database->prepare($query);
        $stmt->bindValue(":registered", $registeredValue, SQLITE3_INTEGER);
        $stmt->bindValue(":name", $playerName, SQLITE3_TEXT);
        
        $result = $stmt->execute();
        
        if ($result !== false) {
            $stmt->close();
        } else {
            $this->getLogger()->error("Failed to set 'registered' value for player: $playerName");
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $playerName = $player->getName();

        if ($this->hasRegistered($player)) {
            $this->showLoginUI($player);
        } else {
            $this->showRegisterUI($player);
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
                $this->setPlayerRegistered($player, true);

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
            if ($playerData != null) {
                $savedPassword = $playerData['password'];
            } else {
                return;
            }

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

}
