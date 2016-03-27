<?php
namespace maru;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\TranslationContainer;
use pocketmine\utils\TextFormat;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;

class ChaseTerrorist extends PluginBase implements Listener {
	private $log;
	private $queue = [];
	public function onEnable() {
		@mkdir($this->getDataFolder());
		$this->load();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onDisable() {
		$this->save();
	}
	public function load() {
		$this->log = (new Config($this->getDataFolder()."log.json", Config::JSON))->getAll();
	}
	public function save() {
		$log = new Config($this->getDataFolder()."log.json", Config::JSON);
		$log->setAll($this->log);
		$log->save();
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if (!$sender->hasPermission("chaseterrorist.commands.log")) {
			$sender->sendMessage(new TranslationContainer(TextFormat::RED."%commands.generic.permission"));
			return true;
		}
		if (!isset($this->queue[$sender->getName()])) {
			$this->queue[$sender->getName()] = true;
			$sender->sendMessage(TextFormat::DARK_AQUA."당신은 이제부터 블럭 터치시 그 블럭의 기록을 볼 수 있습니다.");
			$sender->sendMessage(TextFormat::DARK_AQUA."명령어를 다시 입력하면 터치해도 기록이 보이지 않습니다.");
		} else {
			unset($this->queue[$sender->getName()]);
			$sender->sendMessage(TextFormat::DARK_AQUA."이제 블럭 기록이 보이지 않습니다.");
		}
		return true;
	}
	public function onPlace(BlockPlaceEvent $event) {
		$this->writeLog($event->getPlayer(), new Position($event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ(), $event->getBlock()->getLevel()), "설치");
	}
	public function onBreak(BlockBreakEvent $event) {
		$this->writeLog($event->getPlayer(), new Position($event->getBlock()->getX(), $event ->getBlock()->getY(), $event->getBlock()->getZ(), $event->getBlock()->getLevel()), "파괴");
	}
	public function onTouch(PlayerInteractEvent $event) {
		if (isset($this->queue[$event->getPlayer()->getName()]))
			$this->sendLog($event->getPlayer(), new Position($event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ(), $event->getBlock()->getLevel()));
	}
	private function writeLog(Player $player, Position $pos, $type) {
		if (!isset($this->log[$this->posToString($pos)])) {
			$this->log[$this->posToString($pos)] = [];
		}
		$args["player"] = strtolower($player->getName());
		$args["type"] = $type;
		array_unshift($this->log[$this->posToString($pos)], $args);
		if (isset($this->log[$this->posToString($pos)][5])) {
			unset($this->log[$this->posToString($pos)][5]);
		}
	}
	private function sendLog(CommandSender $player, Position $pos) {
		$player->sendMessage(TextFormat::DARK_AQUA."블럭로그:");
		if (isset($this->log[$this->posToString($pos)])) {
			foreach ($this->log[$this->posToString($pos)] as $index=>$v1) {
				$player->sendMessage(TextFormat::DARK_AQUA.'['. (string)($index+1) .']<'.$v1["player"].'> : '.$v1['type']);
			}
		}
	}
	private function stringToPos($string) {
		$pos = explode(":", $string);
		return new Position($pos[0], $pos[1], $pos[2], $this->getServer()->getLevelByName($pos[3]));
	}
	private function posToString(Position $pos) {
		return "{$pos->getX()}:{$pos->getY()}:{$pos->getZ()}:{$pos->getLevel()->getFolderName()}";
	}
}
?>