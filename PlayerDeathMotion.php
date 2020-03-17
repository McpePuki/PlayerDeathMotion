<?php
/**
 * @name PlayerDeathMotion
 * @author Puki
 * @main PlayerDeathMotion\PlayerDeathMotion
 * @version 1.0.0
 * @api 3.9.5
 */
namespace PlayerDeathMotion;

use pocketmine\Player;

use pocketmine\scheduler\Task;

use pocketmine\event\Listener;

use pocketmine\plugin\PluginBase;

use pocketmine\entity\Entity;

use pocketmine\level\level;

use pocketmine\math\Vector3;

use pocketmine\entity\Human;

use PlayerDeathMotion\DeathNPC;

use pocketmine\event\player\PlayerDeathEvent;

use pocketmine\nbt\tag\{
  CompoundTag, DoubleTag, FloatTag, ListTag, ShortTag, StringTag
};

class PlayerDeathMotion extends PluginBase implements Listener {

  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->getScheduler()->scheduleRepeatingTask(new ClearTask($this), 20);

  }

	public function onDisable(){
		foreach($this->getServer()->getLevels() as $level){
			foreach($level->getEntities() as $entity){
				if($entity instanceof DeathNPC){
					$entity->close();
				}
			}
		}
	}

	public function DeathPlayer(PlayerDeathEvent $ev){
		$player = $ev-> getPlayer();
		$this->CreateDeathNPC($player);
	}

  public $data = [];

public function CreateDeathNPC(Player $player){
  $nbt = new CompoundTag('', [
      new StringTag("CustomName", $player->getName().'님의 시체'),
      new ListTag('Pos', [
          new DoubleTag('', $player->x),
          new DoubleTag('', $player->y -1),
          new DoubleTag('', $player->z)
      ]),
      new ListTag('Motion', [
          new DoubleTag('', 0),
          new DoubleTag('', 0),
          new DoubleTag('', 0)
      ]),
      new ListTag('Rotation', [
          new FloatTag('', 2),
          new FloatTag('', 2)
      ]),
      new CompoundTag('Skin', [
          new StringTag("Name", $player->getSkin()->getSkinId()),
          new StringTag('Data', $player->getSkin()->getSkinData())
      ])
  ]);
	$entity = new DeathNPC($player->getLevel(), $nbt);
  $this->data[$entity->getId()] = 10;
  $entity->getDataPropertyManager()->setBlockPos(DeathNPC::DATA_PLAYER_BED_POSITION, new Vector3($player-> x, $player-> y, $player-> z));
  $entity->setPlayerFlag(DeathNPC::DATA_PLAYER_FLAG_SLEEP, true);
	$entity-> spawnToAll();
}
}

class ClearTask extends Task {
  private $plugin;

  public function __construct(PlayerDeathMotion $plugin){
    $this->plugin = $plugin;
  }

  public function onRun($currentTick){

    foreach($this->plugin->getServer()->getLevels() as $level)
      foreach($level->getEntities() as $entity){
      if(isset($this->plugin->data[$entity->getId()])){
        if($this->plugin->data[$entity->getId()] <= 0){
          unset($this->plugin->data[$entity->getId()]);
          $entity->close();
          return true;
        }
        $entity->setScoreTag($this->plugin->data[$entity->getId()].'초후에 사라질 시체 입니다.');
        $this->plugin->data[$entity->getId()]--;
      }
    }
  }
}

class DeathNPC extends Human{

}
