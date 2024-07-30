<?php

/*
 *  ____   __   __  _   _    ___    ____    ____    ___   _____
 * / ___|  \ \ / / | \ | |  / _ \  |  _ \  / ___|  |_ _| | ____|
 * \___ \   \ V /  |  \| | | | | | | |_) | \___ \   | |  |  _|
 *  ___) |   | |   | |\  | | |_| | |  __/   ___) |  | |  | |___
 * |____/    |_|   |_| \_|  \___/  |_|     |____/  |___| |_____|
 *
 * Plugin permettant de vous nourrir ou alors de nourrir une autre personne
 *
 * @author Synopsie
 * @link https://github.com/Synopsie
 * @version 1.1.1
 *
 */

declare(strict_types=1);

namespace feed;

use Exception;
use feed\command\FeedCommand;
use iriss\listener\CommandListener;
use olymp\PermissionManager;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use function time;

class Main extends PluginBase {
	/** @var array<string, int> */
	private array $cooldown = [];

	public Config $config;

	protected function onLoad() : void {
		$this->saveResource('config.yml');
		$this->config = new Config($this->getDataFolder() . 'config.yml', Config::YAML);
		if ($this->config->get('load-plugin-message', true) === true) {
			$this->getLogger()->info($this->config->get('load-plugin-message-text', '§aFeed plugin has been loaded.'));
		}
		$this->loadCooldown();
	}

	/**
	 * @throws Exception
	 */
	protected function onEnable() : void {
		$config = $this->config;

		require $this->getFile() . 'vendor/autoload.php';

		if ($config->get('enable-plugin-message', true) === true) {
			$this->getLogger()->info($config->get('enable-plugin-message-text', '§aFeed plugin has been enabled.'));
		}

		$permissionManager = new PermissionManager();
		$permissionManager->registerPermission($config->get('permission'), 'feed.command', $permissionManager->getType($config->get('default', 'op')));
		$permissionManager->registerPermission($config->get('permission-feed-others'), 'feed.command.feedothers', $permissionManager->getType($config->get('default-feed-others', 'op')));

		$this->getServer()->getCommandMap()->register(
			'FeedCommand-Command',
			new FeedCommand(
				$this,
				$config->get('name', 'feed'),
				$config->get('description', 'Feed command'),
				$config->get('usage', '/feed [player]'),
				$config->get('aliases', ['eat']),
				$config->get('permission', 'feed.command')
			)
		);

		new CommandListener($this);
	}

	protected function onDisable() : void {
		$this->saveCooldown();
		if ($this->config->get('disable-plugin-message', true) === true) {
			$this->getLogger()->info($this->config->get('disable-plugin-message-text', '§cFeed plugin has been unloaded.'));
		}
	}

	public function addCooldown(string $player) : void {
		if($this->config->get('cooldown', 5) <= 0) {
			return;
		}
		$this->cooldown[$player] = time() + $this->getConfig()->get('cooldown', 5);
	}

	public function getCooldown(string $player) : int {
		return $this->cooldown[$player] ?? 0;
	}

	private function saveCooldown() : void {
		$this->config->set('cooldown-list', $this->cooldown);
		$this->config->save();
	}

	private function loadCooldown() : void {
		$this->cooldown = $this->config->get('cooldown-list', []);
	}

}
