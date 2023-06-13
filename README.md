# AuthUI


**AuthUI** is a feature-rich plugin designed for **PocketMine-MP** servers with API 4.20 and above support, specifically created to enhance authentication and login processes. It provides an intuitive and user-friendly interface for players to securely log in and manage their authentication-related actions. With AuthUI, server administrators can enforce account security measures and provide a seamless login experience for their players! Experience the ultimate security and authentication management for your server with **AuthUI**!

## Key Features

- User-Friendly UI: AuthUI offers a user-friendly interface that allows players to easily log in by entering their password.

- Password Registration: When a new player joins the server, they will be prompted to register a password using a registration UI. Players will enter their desired password and confirm it.

- Login UI: AuthUI presents a login UI to players who have already registered their password. They will be prompted to enter their registered password to log in.

- Login Streak/Count: The plugin keeps track of players' login streaks or login counts, indicating how many times they have successfully logged into the server.

- Login Rewards: AuthUI provides login rewards based on the login count reached by players. You can enable or disable this feature in the `config.yml` file and customize the rewards.

- Per-Player Data: The plugin stores individual player data in the `plugin_data/AuthUI/data/` directory. Each player's data is saved in a YAML file, allowing for easy editing and management.

- Data Reload: The `/loginreload` command allows you to reload all the data without restarting the server. This convenient command ensures that any changes made to the player data are immediately applied.

- Extensive Configuration: The `config.yml` file provides extensive customization options, allowing you to modify all the plugin's messages according to your preferences.

- Forget Password (Coming Soon): A "Forget Password" feature is currently in development, which will assist players in recovering their forgotten passwords.

## Commands

| Command               | Description                        |
|-----------------------|------------------------------------|
| `/loginreload`        | Reloads all player data             |

*Note: AuthUI focuses on practical login and registration logic and does not rely heavily on commands. If you believe any additional commands are necessary, feel free to submit a pull request.*

## Installation

1. Download the latest version of the **AuthUI** plugin.
2. Place the plugin file in the `plugins` folder of your PocketMine-MP server.
3. Restart the server.
4. Enjoy the enhanced authentication and login experience!

## Usage

To use AuthUI, players can simply log in by entering their registered password using the provided UI. New players will be prompted to register a password upon joining the server. The plugin will keep track of their login streaks and provide rewards accordingly. Server administrators can reload player data using the `/loginreload` command without the need to restart the server.

## Contributing

Contributions are welcome! If you encounter any bugs, have feature requests, or suggestions, please open an issue or submit a pull request on the [AuthUI GitHub repository](https://github.com/shreyop200/AuthUI).

## Owner/Credits

This plugin was developed by Shreyansh ([@shreyop200](https://github.com/shreyop200)). Shreyansh has contributed to the development of the entire plugin, including features such as login and registration logic, tracking login streaks, login rewards, and data management. Feel free to explore Shreyansh's [GitHub profile](https://github.com/shreyop200) for more projects and contributions.

## License

This plugin is released under the [Apache License](LICENSE).
